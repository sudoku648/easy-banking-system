<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Form;

use App\BankAccount\Presentation\Dto\OpenAccountNewCustomerDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OpenAccountNewCustomerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'bank_account.username',
                'label_translation_parameters' => [],
                'attr' => ['placeholder' => 'bank_account.placeholder_username'],
                'translation_domain' => 'bank_account',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'bank_account.password',
                'label_translation_parameters' => [],
                'attr' => ['placeholder' => 'bank_account.placeholder_password'],
                'translation_domain' => 'bank_account',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'bank_account.first_name',
                'label_translation_parameters' => [],
                'attr' => ['placeholder' => 'bank_account.placeholder_first_name'],
                'translation_domain' => 'bank_account',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'bank_account.last_name',
                'label_translation_parameters' => [],
                'attr' => ['placeholder' => 'bank_account.placeholder_last_name'],
                'translation_domain' => 'bank_account',
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'bank_account.currency',
                'choices' => [
                    'PLN - Polish Zloty' => 'PLN',
                    'EUR - Euro' => 'EUR',
                ],
                'translation_domain' => 'bank_account',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpenAccountNewCustomerDto::class,
        ]);
    }
}
