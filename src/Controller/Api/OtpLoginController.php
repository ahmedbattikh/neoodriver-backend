<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\OtpLoginService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Route('/api/login', name: 'api_login_otp_', methods: ['POST'])]
final class OtpLoginController extends AbstractController
{
    public function __construct(
        private readonly OtpLoginService $otpService,
        private readonly UserRepository $users,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly \Doctrine\ORM\EntityManagerInterface $em,
    ) {}

    private function getField(Request $request, string $key): string
    {
        $value = '';
        $contentType = (string) $request->headers->get('content-type', '');
        if ($contentType !== '' && str_contains(strtolower($contentType), 'application/json')) {
            try {
                $data = $request->toArray();
                $value = (string) ($data[$key] ?? '');
            } catch (\Throwable) {
                // ignore and fall through
            }
        }
        if ($value === '') {
            $value = (string) $request->request->get($key, '');
        }
        if ($value === '') {
            $value = (string) $request->query->get($key, '');
        }
        return $value;
    }

    #[Route('/email', name: 'request_code')]
    public function requestCode(Request $request): JsonResponse
    {
        $email = $this->getField($request, 'email');
        if ($email === '' && ($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
            return $this->json([
                'error' => 'invalid_request',
                'debugRaw' => $request->getContent(),
                'headers' => $request->headers->all(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $violations = $this->validator->validate($email, [
            new NotBlank(message: 'Email is required.'),
            new EmailConstraint(message: 'Invalid email.'),
            new Length(max: 180),
        ]);
        if (count($violations) > 0) {
            return $this->json([
                'error' => 'invalid_request',
                'details' => (string) $violations,
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->otpService->requestCode($email, $request->getClientIp());

        $payload = [
            'status' => 'ok',
            'ttl' => 300,
            'cooldown' => $result['cooldown'],
        ];
        if ((($_ENV['APP_ENV'] ?? 'prod') === 'dev') && isset($result['code'])) {
            $payload['debugCode'] = $result['code'];
        }

        return $this->json($payload, Response::HTTP_ACCEPTED);
    }

    #[Route('/otp', name: 'login_with_code')]
    public function loginWithCode(Request $request): JsonResponse
    {
        $email = $this->getField($request, 'email');
        $code = $this->getField($request, 'code');
        if (($email === '' || $code === '') && ($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
            return $this->json([
                'error' => 'invalid_request',
                'debugRaw' => $request->getContent(),
                'headers' => $request->headers->all(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $violations = $this->validator->validate($email, [
            new NotBlank(message: 'Email is required.'),
            new EmailConstraint(message: 'Invalid email.'),
            new Length(max: 180),
        ]);
        if (count($violations) > 0 || $code === '') {
            return $this->json([
                'error' => 'invalid_request',
                'details' => count($violations) > 0 ? (string) $violations : 'Code is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $valid = $this->otpService->verifyCode($email, $code);
        if (!$valid) {
            return $this->json([
                'error' => 'invalid_code',
                'message' => 'The provided code is invalid or expired.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find or create the user
        $user = $this->users->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $user = new User();
            $user->setEmail($email);
            $randomPassword = bin2hex(random_bytes(16));
            $user->setPassword($this->passwordHasher->hashPassword($user, $randomPassword));
            $this->em->persist($user);
            $this->em->flush();
        }


        // Issue JWT access token
        $token = $this->jwtManager->create($user);

        // Issue refresh token using the bundle's generator API
        $refreshTtl = (int) ($_ENV['JWT_REFRESH_TTL'] ?? '2592000');
        $refreshTokenEntity = $this->refreshTokenGenerator->createForUserWithTtl($user, $refreshTtl);
        $this->refreshTokenManager->save($refreshTokenEntity);

        return $this->json([
            'token' => $token,
            'refreshToken' => $refreshTokenEntity->getRefreshToken(),
            'tokenType' => 'Bearer',
            'expiresIn' => (int) ($_ENV['JWT_TTL'] ?? '3600'),
            'refreshTtl' => $refreshTtl,
        ]);
    }
}