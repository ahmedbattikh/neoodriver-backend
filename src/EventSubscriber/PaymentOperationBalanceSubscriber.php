<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Balance;
use App\Entity\PaymentOperation;
use App\Enum\PaymentMethodType;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;

final class PaymentOperationBalanceSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return ['postPersist', 'postUpdate', 'postRemove'];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $entity = $args->getObject();
        if (!$entity instanceof PaymentOperation) {
            return;
        }
        $this->applyContribution($em, $entity->getDriver(), $entity->getDirection(), $entity->getPaymentMethodEnum(), $entity->getAmount(), 1.0);
        $em->flush();
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $entity = $args->getObject();
        if (!$entity instanceof PaymentOperation) {
            return;
        }
        $uow = $em->getUnitOfWork();
        $cs = $uow->getEntityChangeSet($entity);
        $oldDirection = (string) ($cs['direction'][0] ?? $entity->getDirection());
        $newDirection = (string) $entity->getDirection();
        $oldAmount = (string) ($cs['amount'][0] ?? $entity->getAmount());
        $newAmount = (string) $entity->getAmount();
        $oldMethod = $cs['paymentMethod'][0] ?? $entity->getPaymentMethodEnum();
        $newMethod = $entity->getPaymentMethodEnum();
        $driver = $entity->getDriver();
        $this->applyContribution($em, $driver, $oldDirection, $oldMethod, $oldAmount, -1.0);
        $this->applyContribution($em, $driver, $newDirection, $newMethod, $newAmount, 1.0);
        $em->flush();
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $entity = $args->getObject();
        if (!$entity instanceof PaymentOperation) {
            return;
        }
        $this->applyContribution($em, $entity->getDriver(), $entity->getDirection(), $entity->getPaymentMethodEnum(), $entity->getAmount(), -1.0);
        $em->flush();
    }

    private function applyContribution($em, $driver, ?string $direction, mixed $paymentMethod, ?string $amount, float $sign): void
    {
        $balance = $driver->getBalance() ?? $em->getRepository(Balance::class)->findOneBy(['driver' => $driver]);
        if (!$balance instanceof Balance) {
            $balance = new Balance();
            $driver->setBalance($balance);
            $em->persist($balance);
        }
        $dir = strtolower((string) ($direction ?? ''));
        $amt = (float) ((string) ($amount ?? '0')) * $sign;
        $methodVal = ($paymentMethod instanceof PaymentMethodType) ? $paymentMethod->value : strtoupper((string) $paymentMethod);
        $sold = (float) $balance->getSold();
        $debit = (float) $balance->getTotalDebit();
        if ($methodVal === PaymentMethodType::CASH->value) {
            $sold -= $amt;
            $debit += $amt;
        } elseif ($dir === 'in' || $dir === 'credit') {
            $sold += $amt;
        } elseif ($dir === 'out' || $dir === 'debit') {
            $sold -= $amt;
            $debit += $amt;
        }
        $balance->setSold(number_format($sold, 3, '.', ''));
        $balance->setTotalDebit(number_format($debit, 3, '.', ''));
        $balance->setLastUpdate(new \DateTimeImmutable('now'));
    }
}
