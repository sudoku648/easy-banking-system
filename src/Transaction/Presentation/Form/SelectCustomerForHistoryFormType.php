<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Form;

use App\Transaction\Presentation\Dto\SelectCustomerForHistoryDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SelectCustomerForHistoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<int, array{id: string, username: string, firstName: string, lastName: string, fullName: string, isActive: bool}> $customers */
        $customers = $options['customers'] ?? [];

        /** @var array<int, array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
        $accounts = $options['accounts'] ?? [];

        // Build customer choices
        $customerChoices = [];
        foreach ($customers as $customer) {
            $label = $customer['fullName'] . ' (' . $customer['username'] . ')';
            $customerChoices[$label] = $customer['id'];
        }

        // Build bank account choices with customer ID attributes
        $accountChoices = [];
        $accountAttributes = [];
        foreach ($accounts as $account) {
            $balance = (float) $account['balance'] / 100;
            $label = $account['iban'] . ' (' . number_format($balance, 2) . ' ' . $account['currency'] . ')';
            $accountChoices[$label] = $account['id'];
            $accountAttributes[$account['id']] = ['data-customer-id' => $account['customerId']];
        }

        $builder
            ->add('customerId', ChoiceType::class, [
                'label' => 'transaction.select_customer',
                'choices' => $customerChoices,
                'placeholder' => 'transaction.placeholder_select_customer',
                'required' => false,
                'translation_domain' => 'transaction',
                'help' => 'transaction.help_select_customer_for_history',
            ])
            ->add('bankAccountId', ChoiceType::class, [
                'label' => 'transaction.select_bank_account',
                'choices' => $accountChoices,
                'choice_attr' => static fn (string $choice): array => $accountAttributes[$choice] ?? [],
                'placeholder' => 'transaction.placeholder_select_bank_account',
                'required' => false,
                'translation_domain' => 'transaction',
                'help' => 'transaction.help_select_account_for_history',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SelectCustomerForHistoryDto::class,
            'customers' => [],
            'accounts' => [],
        ]);
    }
}
