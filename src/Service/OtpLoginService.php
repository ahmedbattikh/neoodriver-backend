<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\LoginOtp;
use App\Repository\LoginOtpRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class OtpLoginService
{
    private LoginOtpRepository $repository;
    private MailerInterface $mailer;
    private string $secret;
    private int $otpTtlSeconds;
    private int $resendCooldownSeconds;
    private int $maxAttempts;
    private string $fromEmail;

    public function __construct(
        LoginOtpRepository $repository,
        MailerInterface $mailer,
        ParameterBagInterface $params
    ) {
        $this->repository = $repository;
        $this->mailer = $mailer;
        $this->secret = (string) $params->get('kernel.secret');
        $this->otpTtlSeconds = 300; // 5 minutes
        $this->resendCooldownSeconds = 60; // 1 minute cooldown
        $this->maxAttempts = 5;
        $this->fromEmail = 'no-reply@neoodriver.test';
    }

    /**
     * Generate an OTP, store hashed code, and email the code.
     * Returns the created LoginOtp and the plain code (only to be used for emailing).
     */
    public function requestCode(string $email, ?string $ipAddress = null): array
    {
        $existing = $this->repository->findLatestActiveByEmail($email);
        if ($existing !== null) {
            $cooldownUntil = $existing->getCreatedAt()->modify('+' . $this->resendCooldownSeconds . ' seconds');
            if ($cooldownUntil > new \DateTimeImmutable('now')) {
                return ['otp' => $existing, 'code' => null, 'cooldown' => $this->resendCooldownSeconds];
            }
        }

        $code = (string) random_int(100000, 999999);
        $hash = hash_hmac('sha256', $code, $this->secret);
        $expiresAt = new \DateTimeImmutable('+' . $this->otpTtlSeconds . ' seconds');
        $otp = new LoginOtp($email, $hash, $expiresAt, $ipAddress);
        $this->repository->save($otp);

        $this->sendEmail($email, $code);

        return ['otp' => $otp, 'code' => $code, 'cooldown' => $this->resendCooldownSeconds];
    }

    /**
     * Verify a code for the given email. Returns true if valid; consumes OTP on success.
     */
    public function verifyCode(string $email, string $code): bool
    {
        $otp = $this->repository->findLatestActiveByEmail($email);
        if ($otp === null) {
            return false;
        }

        if ($otp->getAttempts() >= $this->maxAttempts) {
            return false;
        }

        $hash = hash_hmac('sha256', $code, $this->secret);
        if (hash_equals($otp->getCodeHash(), $hash) && !$otp->isExpired()) {
            $otp->consume();
            $this->repository->save($otp);
            return true;
        }

        $otp->incrementAttempts();
        $this->repository->save($otp);
        return false;
    }

    private function sendEmail(string $toEmail, string $code): void
    {
        $email = (new Email())
            ->from(new Address($this->fromEmail, 'NeoDriver Auth'))
            ->to($toEmail)
            ->subject('Your NeoDriver login code')
            ->text(sprintf("Your login code is: %s\nIt expires in %d minutes.", $code, (int) round($this->otpTtlSeconds / 60)))
            ->html(sprintf('<p>Your login code is: <strong>%s</strong></p><p>It expires in %d minutes.</p>', $code, (int) round($this->otpTtlSeconds / 60)));

        $this->mailer->send($email);
    }
}