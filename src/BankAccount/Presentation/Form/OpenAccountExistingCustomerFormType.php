<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Form;

use App\BankAccount\Presentation\Dto\OpenAccountExistingCustomerDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OpenAccountExistingCustomerFormType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<int, array{id: string, username: string, firstName: string, lastName: string, fullName: string, isActive: bool}> $customers */
        $customers = $options['customers'] ?? [];

        $builder
            ->add('customerId', ChoiceType::class, [
                'label' => 'Customer',
                'choices' => $customers,
                'choice_label' => function (mixed $customer): string {
                    if (!\is_array($customer)) {
                        return '';
                    }
                    /** @var array{fullName: string, username: string} $customer */
                    return $customer['fullName'] . ' (' . $customer['username'] . ')';
                },
                'choice_value' => function (mixed $customer): string {
                    if (!\is_array($customer)) {
                        return '';
                    }
                    /** @var array{id: string} $customer */
                    return $customer['id'];
                },
                'placeholder' => '-- Select customer --',
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Currency',
                'choices' => [
                    'PLN - Polish Zloty' => 'PLN',
                    'EUR - Euro' => 'EUR',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpenAccountExistingCustomerDto::class,
            'customers' => [],
        ]);
    }
}
