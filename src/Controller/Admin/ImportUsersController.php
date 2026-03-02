<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CompanyDocuments;
use App\Entity\Driver;
use App\Entity\DriverDocuments;
use App\Entity\DriverIntegration;
use App\Entity\DriverIntegrationAccount;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\UserRole;
use App\Service\BackofficeMenuBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class ImportUsersController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly BackofficeMenuBuilder $menuBuilder,
    ) {}

    public function __invoke(Request $request): Response
    {
        $result = null;
        $errors = [];
        $integrations = $this->em->getRepository(DriverIntegration::class)->findBy(['enabled' => true], ['name' => 'ASC']);
        $selectedIntegrationId = (int) $request->request->get('integrationId', 0);
        $selectedIntegration = $selectedIntegrationId > 0
            ? $this->em->getRepository(DriverIntegration::class)->find($selectedIntegrationId)
            : null;

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('import_users', $token)) {
                $errors[] = 'Invalid form token.';
            } elseif (!$selectedIntegration instanceof DriverIntegration) {
                $errors[] = 'Select a valid integration before importing.';
            } else {
                $payload = (string) $request->request->get('jsonPayload', '');
                $file = $request->files->get('jsonFile');
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $payload = (string) file_get_contents($file->getPathname());
                }
                $payload = trim($payload);
                if ($payload === '') {
                    $errors[] = 'Provide a JSON payload or upload a JSON file.';
                } else {
                    $decoded = json_decode($payload, true);
                    if (!is_array($decoded)) {
                        $errors[] = 'Invalid JSON.';
                    } else {
                        $drivers = $decoded['data']['drivers'] ?? $decoded['drivers'] ?? null;
                        if (!is_array($drivers)) {
                            $errors[] = 'Missing drivers array in JSON payload.';
                        } else {
                            $result = $this->importDrivers($drivers, $selectedIntegration);
                        }
                    }
                }
            }
        }

        foreach ($errors as $error) {
            $this->addFlash('danger', $error);
        }

        return $this->render('backoffice/users/import.html.twig', [
            'menuItems' => $this->menuBuilder->build(),
            'currentPath' => $request->getPathInfo(),
            'result' => $result,
            'integrations' => $integrations,
            'selectedIntegrationId' => $selectedIntegrationId,
        ]);
    }

    private function importDrivers(array $drivers, DriverIntegration $integration): array
    {
        $created = 0;
        $skipped = 0;
        $errors = 0;
        $seenEmails = [];

        foreach ($drivers as $index => $driverRow) {
            if (!is_array($driverRow)) {
                $errors++;
                continue;
            }
            $email = strtolower(trim((string) ($driverRow['email'] ?? '')));
            if ($email === '') {
                $errors++;
                continue;
            }
            $externalDriverId = trim((string) ($driverRow['driver_uuid'] ?? ''));
            if ($externalDriverId === '') {
                $errors++;
                continue;
            }
            if (isset($seenEmails[$email])) {
                $skipped++;
                continue;
            }
            $seenEmails[$email] = true;
            $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing instanceof User) {
                $skipped++;
                continue;
            }

            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($this->safeString($driverRow['first_name'] ?? null));
            $user->setLastName($this->safeString($driverRow['last_name'] ?? null));
            $user->setMobileNumber($this->safeString($driverRow['phone'] ?? null));
            $user->setRoles(['ROLE_DRIVER']);
            $user->setRole(UserRole::DRIVER);
            $user->setVerified(($driverRow['state'] ?? '') === 'active');
            $plainPassword = bin2hex(random_bytes(6));
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

            $driver = new Driver();
            $driver->setUser($user);
            $driver->setActive(($driverRow['state'] ?? '') === 'active');
            $user->setDriverProfile($driver);

            $driverDocs = new DriverDocuments();
            $driverDocs->setDriver($driver);
            $companyDocs = new CompanyDocuments();
            $companyDocs->setDriver($driver);
            $integrationAccount = new DriverIntegrationAccount();
            $integrationAccount->setDriver($driver);
            $integrationAccount->setIntegration($integration);
            $integrationAccount->setIdDriver($externalDriverId);

            $this->em->persist($user);
            $this->em->persist($driver);
            $this->em->persist($driverDocs);
            $this->em->persist($companyDocs);
            $this->em->persist($integrationAccount);

            $vehicleRow = $driverRow['active_vehicle'] ?? null;
            if (is_array($vehicleRow)) {
                $vehicle = new Vehicle();
                $vehicle->setDriver($driver);
                $regNumber = $this->safeString($vehicleRow['reg_number'] ?? null);
                if ($regNumber !== null && $regNumber !== '') {
                    $vehicle->setRegistrationNumber($regNumber);
                }
                $model = $this->safeString($vehicleRow['model'] ?? null);
                if ($model !== null && $model !== '') {
                    $vehicle->setModel($model);
                    $make = trim((string) strtok($model, ' '));
                    if ($make !== '') {
                        $vehicle->setMake($make);
                    }
                }
                $year = (int) ($vehicleRow['year'] ?? 0);
                if ($year > 0) {
                    $vehicle->setFirstRegistrationYear($year);
                }
                $this->em->persist($vehicle);
            }

            $created++;
        }

        $this->em->flush();

        return [
            'total' => count($drivers),
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function safeString(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);
        return $value === '' ? null : $value;
    }
}
