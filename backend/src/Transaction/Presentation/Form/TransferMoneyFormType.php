<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Form;

use App\Transaction\Presentation\Dto\TransferMoneyDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TransferMoneyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<int, array{id: string, iban: string, balance: int, currency: string}> $accountsData */
        $accountsData = $options['accounts'] ?? [];

        // Build choices array with labels as keys and IDs as values
        $choices = [];
        foreach ($accountsData as $account) {
            $label = $account['iban'] . ' (' . number_format((float) $account['balance'] / 100, 2) . ' ' . $account['currency'] . ')';
            $choices[$label] = $account['id'];
        }

        $builder
            ->add('fromBankAccountId', ChoiceType::class, [
                'label' => 'transaction.transfer',
                'choices' => $choices,
                'placeholder' => 'transaction.placeholder_select_source_account',
                'translation_domain' => 'transaction',
            ])
            ->add('toIban', TextType::class, [
                'label' => 'transaction.transfer',
                'attr' => ['placeholder' => 'transaction.placeholder_to_iban'],
                'translation_domain' => 'transaction',
            ])
            ->add('amount', NumberType::class, [
                'label' => 'transaction.amount',
                'scale' => 2,
                'attr' => ['placeholder' => 'transaction.placeholder_amount', 'step' => '0.01'],
                'translation_domain' => 'transaction',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferMoneyDto::class,
            'accounts' => [],
        ]);
    }
}
