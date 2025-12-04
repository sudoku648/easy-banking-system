<?php

declare(strict_types=1);

namespace App\UserManagement\Symfony\DependencyInjection;

use App\UserManagement\Domain\ValueObject\Locale;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to dynamically configure available locales from Locale enum
 */
final class LocaleConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Get all locale codes from enum
        $locales = Locale::codes();
        $defaultLocale = Locale::default()->value;

        // Set framework configuration parameters
        $container->setParameter('kernel.enabled_locales', $locales);
        $container->setParameter('kernel.default_locale', $defaultLocale);
    }
}
