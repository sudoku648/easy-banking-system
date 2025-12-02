<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Form;

use App\BankAccount\Presentation\Dto\CorrespondenceAddressDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CorrespondenceAddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class, [
                'label' => 'bank_account.correspondence_street',
                'attr' => ['placeholder' => 'bank_account.placeholder_street'],
                'translation_domain' => 'bank_account',
            ])
            ->add('city', TextType::class, [
                'label' => 'bank_account.correspondence_city',
                'attr' => ['placeholder' => 'bank_account.placeholder_city'],
                'translation_domain' => 'bank_account',
            ])
            ->add('postalCode1', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'XX',
                    'maxlength' => 2,
                    'class' => 'form-control postal-code-part',
                ],
                'translation_domain' => 'bank_account',
            ])
            ->add('postalCode2', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'XXX',
                    'maxlength' => 3,
                    'class' => 'form-control postal-code-part',
                ],
                'translation_domain' => 'bank_account',
            ])
            ->add('country', ChoiceType::class, [
                'label' => 'bank_account.correspondence_country',
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CorrespondenceAddressDto::class,
        ]);
    }
}
