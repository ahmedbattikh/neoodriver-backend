<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AppLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

final class RequestLogSubscriber implements EventSubscriberInterface
{
    private const START_ATTR = '_app_log_start';
    private const SKIP_PREFIXES = ['/_wdt', '/_profiler', '/_error'];

    public function __construct(private readonly EntityManagerInterface $em, private readonly Security $security) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 100],
            KernelEvents::RESPONSE => ['onResponse', -100],
            KernelEvents::EXCEPTION => ['onException', -100],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        if ($this->shouldSkip($request->getPathInfo())) {
            return;
        }
        $request->attributes->set(self::START_ATTR, microtime(true));
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->em->isOpen()) {
            return;
        }
        $request = $event->getRequest();
        if ($this->shouldSkip($request->getPathInfo())) {
            return;
        }
        $start = (float) $request->attributes->get(self::START_ATTR, microtime(true));
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $response = $event->getResponse();
        $log = new AppLog('info', 'request', $request->getMethod(), $request->getPathInfo(), $response->getStatusCode());
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
        }
        $log->setIpAddress($request->getClientIp());
        $log->setDurationMs($durationMs);
        $log->setContext([
            'route' => $request->attributes->get('_route'),
            'query' => $request->query->all(),
        ]);
        $this->em->persist($log);
        $this->em->flush();
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->em->isOpen()) {
            return;
        }
        $request = $event->getRequest();
        if ($this->shouldSkip($request->getPathInfo())) {
            return;
        }
        $throwable = $event->getThrowable();
        $statusCode = $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : 500;
        $log = new AppLog('error', $throwable::class, $request->getMethod(), $request->getPathInfo(), $statusCode);
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
        }
        $log->setIpAddress($request->getClientIp());
        $log->setContext([
            'route' => $request->attributes->get('_route'),
            'error' => $throwable->getMessage(),
        ]);
        $this->em->persist($log);
        $this->em->flush();
    }

    private function shouldSkip(string $path): bool
    {
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
