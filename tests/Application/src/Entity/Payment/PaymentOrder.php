<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusPausePayPlugin\App\Entity\Payment;

use Doctrine\ORM\Mapping as ORM;
use \Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrder as BasePaymentOrder;

/**
 * @ORM\Entity
 * @ORM\Table(name="webgriffe_sylius_pausepay_payment_order")
 */
class PaymentOrder extends BasePaymentOrder
{
}
