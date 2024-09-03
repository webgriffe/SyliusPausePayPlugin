<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Validator;

use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PausePayPaymentMethodUniqueValidator extends ConstraintValidator
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {
    }

    /**
     * @param mixed|PaymentMethodInterface $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof PaymentMethodInterface) {
            throw new UnexpectedValueException($value, PaymentMethodInterface::class);
        }

        if (!$constraint instanceof PausePayPaymentMethodUnique) {
            throw new UnexpectedValueException($constraint, PausePayPaymentMethodUnique::class);
        }

        $gatewayConfig = $value->getGatewayConfig();
        /** @psalm-suppress DeprecatedMethod */
        if ($gatewayConfig === null || $gatewayConfig->getFactoryName() !== PausePayApi::GATEWAY_CODE) {
            return;
        }

        // todo: this should consider if the payment method is enabled on the channel
        /** @var PaymentMethodInterface[] $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->findAll();
        /** @psalm-suppress DeprecatedMethod */
        $paymentMethodsWithSameGatewayConfig = array_filter(
            $paymentMethods,
            static fn (PaymentMethodInterface $paymentMethod) => $paymentMethod->getGatewayConfig()?->getFactoryName() === $gatewayConfig->getFactoryName(),
        );
        if (count($paymentMethodsWithSameGatewayConfig) > 1 ||
            (count($paymentMethodsWithSameGatewayConfig) === 1 && reset(
                $paymentMethodsWithSameGatewayConfig,
            ) !== $value)
        ) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('gatewayConfig')
                ->addViolation();
        }
    }
}
