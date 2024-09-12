<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Doctrine\ORM;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrder;
use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrderInterface;
use Webgriffe\SyliusPausePayPlugin\Repository\PaymentOrderRepositoryInterface;

/**
 * @extends ServiceEntityRepository<PaymentOrderInterface>
 */
final class PaymentOrderRepository extends ServiceEntityRepository implements PaymentOrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentOrder::class);
    }

    public function add(PaymentOrderInterface $paymentOrder): void
    {
        $this->getEntityManager()->persist($paymentOrder);
        $this->getEntityManager()->flush();
    }

    public function findOneByPausePayOrderId(string $pausePayOrderId): ?PaymentOrderInterface
    {
        return $this->findOneBy(['orderId' => $pausePayOrderId]);
    }

    public function remove(PaymentOrderInterface $paymentOrder): void
    {
        $this->getEntityManager()->remove($paymentOrder);
        $this->getEntityManager()->flush();
    }
}
