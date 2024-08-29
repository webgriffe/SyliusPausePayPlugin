<?php

declare(strict_types=1);

namespace Webgriffe\SyliusPausePayPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Webgriffe\SyliusPausePayPlugin\Payum\PausePayApi;

final class SyliusPausePayGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(PausePayApi::API_KEY_FIELD_NAME, PasswordType::class, [
                'label' => 'webgriffe_sylius_pausepay.form.gateway_configuration.api_key',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add(PausePayApi::SANDBOX_FIELD_NAME, CheckboxType::class, [
                'label' => 'webgriffe_sylius_pausepay.form.gateway_configuration.sandbox',
                'required' => false,
            ]);
    }
}
