<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Form;

use App\BankAccount\Presentation\Dto\BlockDebitCardDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BlockDebitCardFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('debitCardId', ChoiceType::class, [
                'label' => 'bank_account.debit_card',
                'choices' => $options['debit_cards'],
                'placeholder' => 'bank_account.placeholder_select_debit_card_to_block',
                'translation_domain' => 'bank_account',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlockDebitCardDto::class,
            'debit_cards' => [],
        ]);

        $resolver->setAllowedTypes('debit_cards', 'array');
    }
}
