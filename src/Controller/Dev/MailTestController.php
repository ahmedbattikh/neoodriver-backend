<?php
declare(strict_types=1);

namespace App\Controller\Dev;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class MailTestController
{
    public function send(Request $request, MailerInterface $mailer): JsonResponse
    {
        $to = (string) $request->query->get('to', 'test@neoodriver.com');
        $subject = (string) $request->query->get('subject', 'Dev Mailhog Test');

        $email = (new Email())
            ->from('dev@neoodriver.com')
            ->to($to)
            ->subject($subject)
            ->text('Hello from local dev! This message should appear in Mailhog.')
            ->html('<p>Hello from local dev!</p><p>This message should appear in <strong>Mailhog</strong>.</p>');

        $mailer->send($email);

        return new JsonResponse(['status' => 'sent', 'to' => $to, 'subject' => $subject]);
    }
}