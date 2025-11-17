<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Storage\R2Client;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostPersistEventArgs;

final class UserSubscriber implements EventSubscriber
{
    public function __construct(private readonly R2Client $r2) {}

    public function getSubscribedEvents(): array
    {
        return ['postPersist'];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof User) {
            return;
        }
        $reference = $entity->getReference();
        if (!$reference) {
            return;
        }
        $this->r2->ensureUserFolders($reference);
    }
}