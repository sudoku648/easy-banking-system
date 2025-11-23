<?php

declare(strict_types=1);

namespace App\UserManagement\Symfony\Twig;

use App\UserManagement\Domain\ValueObject\Locale;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LocaleExtension extends AbstractExtension
{
    private const array LOCALE_FLAGS = [
        'pl' => '🇵🇱',
        'en' => '🇬🇧',
    ];

    private const array LOCALE_NAMES = [
        'pl' => 'Polski',
        'en' => 'English',
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('available_locales', $this->getAvailableLocales(...)),
            new TwigFunction('locale_flag', $this->getLocaleFlag(...)),
            new TwigFunction('locale_name', $this->getLocaleName(...)),
        ];
    }

    /**
     * Get all available locales
     * @return Locale[]
     */
    public function getAvailableLocales(): array
    {
        return Locale::cases();
    }

    /**
     * Get flag emoji for locale
     */
    public function getLocaleFlag(string $locale): string
    {
        return self::LOCALE_FLAGS[$locale] ?? '🌐';
    }

    /**
     * Get display name for locale
     */
    public function getLocaleName(string $locale): string
    {
        return self::LOCALE_NAMES[$locale] ?? $locale;
    }
}
