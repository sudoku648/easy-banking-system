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
                'label' => 'From Account',
                'choices' => $choices,
                'placeholder' => '-- Select source account --',
            ])
            ->add('toIban', TextType::class, [
                'label' => 'To IBAN',
                'attr' => ['placeholder' => 'PL12345678901234567890123456'],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Amount',
                'scale' => 2,
                'attr' => ['placeholder' => '0.00', 'step' => '0.01'],
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
