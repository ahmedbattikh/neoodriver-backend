<?php

declare(strict_types=1);

namespace App\Controller\Api\Secure;

use App\Entity\User;
use App\Entity\Driver;
use App\Entity\DriverDocuments;
use App\Entity\Vehicle;
use App\Form\UserWizard\UserStepType;
use App\Form\UserWizard\DriverDocumentsStepType;
use App\Form\Api\VehicleUpdateType;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/secure', name: 'api_secure_')]
final class EditController extends AbstractFOSRestController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    private function data(Request $request): array
    {
        $contentType = (string) $request->headers->get('content-type', '');
        if ($contentType !== '' && str_contains(strtolower($contentType), 'application/json')) {
            try {
                return $request->toArray();
            } catch (\Throwable) {
                return [];
            }
        }
        return $request->request->all();
    }

    private function filterNulls(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if ($v !== null) {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    #[Route('/user', name: 'user_update', methods: ['PATCH'])]
    public function updateUser(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $form = $this->createForm(UserStepType::class, $user);
        $form->submit($this->filterNulls($this->data($request)), false);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $view = $this->view(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $this->em->flush();
        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($user, Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }

    #[Route('/driver-documents', name: 'driver_documents_update', methods: ['PATCH'])]
    public function updateDriverDocuments(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $driver = new Driver();
            $driver->setUser($user);
            $this->em->persist($driver);
            $this->em->flush();
        }
        $docs = $driver->getDocuments() ?? new DriverDocuments();
        $docs->setDriver($driver);
        $form = $this->createForm(DriverDocumentsStepType::class, $docs);
        $form->submit($this->filterNulls($this->data($request)), false);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $view = $this->view(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        if ($docs->getId() === null) {
            $this->em->persist($docs);
        }
        $driver->setDocuments($docs);
        $this->em->flush();
        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($docs, Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }

    #[Route('/vehicle/{id}', name: 'vehicle_update', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function updateVehicle(Request $request, #[CurrentUser] ?User $user, int $id): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $vehicle = $this->em->getRepository(Vehicle::class)->find($id);
        if (!$vehicle instanceof Vehicle) {
            $view = $this->view(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver || $vehicle->getDriver() !== $driver) {
            $view = $this->view(['error' => 'forbidden'], Response::HTTP_FORBIDDEN);
            return $this->handleView($view);
        }
        $form = $this->createForm(VehicleUpdateType::class, $vehicle);
        $form->submit($this->filterNulls($this->data($request)), false);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $view = $this->view(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $this->em->flush();
        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($vehicle, Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }
}
