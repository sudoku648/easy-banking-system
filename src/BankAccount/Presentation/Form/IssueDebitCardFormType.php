<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Form;

use App\BankAccount\Presentation\Dto\IssueDebitCardDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class IssueDebitCardFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bankAccountId', ChoiceType::class, [
                'label' => 'bank_account.bank_account',
                'choices' => $options['bank_accounts'],
                'placeholder' => 'bank_account.placeholder_select_account_to_issue_debit_card',
                'translation_domain' => 'bank_account',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IssueDebitCardDto::class,
            'bank_accounts' => [],
        ]);

        $resolver->setAllowedTypes('bank_accounts', 'array');
    }
}
