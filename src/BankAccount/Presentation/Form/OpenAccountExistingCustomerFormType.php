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

        // Build choices array with labels as keys and IDs as values
        $choices = [];
        foreach ($customers as $customer) {
            $label = $customer['fullName'] . ' (' . $customer['username'] . ')';
            $choices[$label] = $customer['id'];
        }

        $builder
            ->add('customerId', ChoiceType::class, [
                'label' => 'bank_account.select_customer',
                'choices' => $choices,
                'placeholder' => 'bank_account.placeholder_select_customer',
                'translation_domain' => 'bank_account',
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'bank_account.currency',
                'choices' => [
                    'PLN - Polish Zloty' => 'PLN',
                    'EUR - Euro' => 'EUR',
                    'USD - US Dollar' => 'USD',
                    'GBP - British Pound' => 'GBP',
                ],
                'translation_domain' => 'bank_account',
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
