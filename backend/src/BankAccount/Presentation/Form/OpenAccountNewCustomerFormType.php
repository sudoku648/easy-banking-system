<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Form;

use App\BankAccount\Presentation\Dto\OpenAccountNewCustomerDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OpenAccountNewCustomerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Personal information
            ->add('username', TextType::class, [
                'label' => 'bank_account.username',
                'attr' => ['placeholder' => 'bank_account.placeholder_username'],
                'translation_domain' => 'bank_account',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'bank_account.password',
                'attr' => ['placeholder' => 'bank_account.placeholder_password'],
                'translation_domain' => 'bank_account',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'bank_account.first_name',
                'attr' => ['placeholder' => 'bank_account.placeholder_first_name'],
                'translation_domain' => 'bank_account',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'bank_account.last_name',
                'attr' => ['placeholder' => 'bank_account.placeholder_last_name'],
                'translation_domain' => 'bank_account',
            ])
            // Permanent Residence Address
            ->add('permanentResidenceStreet', TextType::class, [
                'label' => 'bank_account.permanent_residence_street',
                'attr' => ['placeholder' => 'bank_account.placeholder_street'],
                'translation_domain' => 'bank_account',
            ])
            ->add('permanentResidenceCity', TextType::class, [
                'label' => 'bank_account.permanent_residence_city',
                'attr' => ['placeholder' => 'bank_account.placeholder_city'],
                'translation_domain' => 'bank_account',
            ])
            ->add('permanentResidencePostalCode1', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'XX',
                    'maxlength' => 2,
                    'class' => 'form-control postal-code-part',
                ],
                'translation_domain' => 'bank_account',
            ])
            ->add('permanentResidencePostalCode2', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'XXX',
                    'maxlength' => 3,
                    'class' => 'form-control postal-code-part',
                ],
                'translation_domain' => 'bank_account',
            ])
            ->add('permanentResidenceCountry', ChoiceType::class, [
                'label' => 'bank_account.permanent_residence_country',
                'choices' => [
                    'Poland' => 'Poland',
                    'Germany' => 'Germany',
                    'France' => 'France',
                    'United Kingdom' => 'United Kingdom',
                    'Spain' => 'Spain',
                    'Italy' => 'Italy',
                    'Netherlands' => 'Netherlands',
                    'Other' => 'Other',
                ],
                'translation_domain' => 'bank_account',
                'placeholder' => 'bank_account.placeholder_select_country',
            ])
            // Correspondence address checkbox
            ->add('sameAsPermament', CheckboxType::class, [
                'label' => 'bank_account.same_as_permanent',
                'required' => false,
                'translation_domain' => 'bank_account',
                'attr' => [
                    'data-toggle-target' => '#correspondence-address-group',
                ],
            ])
            // Correspondence Addresses Collection
            ->add('correspondenceAddresses', CollectionType::class, [
                'entry_type' => CorrespondenceAddressFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'attr' => [
                    'class' => 'correspondence-addresses-collection',
                ],
            ])
            // Account settings
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
            'data_class' => OpenAccountNewCustomerDto::class,
        ]);
    }
}
