<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Form;

use App\UserManagement\Presentation\Dto\ChangePasswordDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'user.current_password',
                'attr' => ['placeholder' => 'user.placeholder_current_password'],
                'translation_domain' => 'app',
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'user.new_password',
                'attr' => ['placeholder' => 'user.placeholder_new_password'],
                'translation_domain' => 'app',
            ])
            ->add('confirmNewPassword', PasswordType::class, [
                'label' => 'user.confirm_new_password',
                'attr' => ['placeholder' => 'user.placeholder_confirm_new_password'],
                'translation_domain' => 'app',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePasswordDto::class,
        ]);
    }
}
