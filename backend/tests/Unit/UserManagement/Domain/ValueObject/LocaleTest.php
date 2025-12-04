<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserManagement\Domain\ValueObject;

use App\UserManagement\Domain\ValueObject\Locale;
use PHPUnit\Framework\TestCase;

final class LocaleTest extends TestCase
{
    public function testItCreatesPolishLocale(): void
    {
        $locale = Locale::POLISH;

        $this->assertTrue($locale->isPolish());
        $this->assertFalse($locale->isEnglish());
        $this->assertSame('pl', $locale->value);
    }

    public function testItCreatesEnglishLocale(): void
    {
        $locale = Locale::ENGLISH;

        $this->assertTrue($locale->isEnglish());
        $this->assertFalse($locale->isPolish());
        $this->assertSame('en', $locale->value);
    }

    public function testItCreatesLocaleFromString(): void
    {
        $locale = Locale::fromString('pl');

        $this->assertSame('pl', $locale->value);
        $this->assertSame(Locale::POLISH, $locale);
    }

    public function testItThrowsExceptionForInvalidLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid locale: invalid. Must be one of: pl, en');

        Locale::fromString('invalid');
    }

    public function testItComparesTwoLocales(): void
    {
        $locale1 = Locale::POLISH;
        $locale2 = Locale::fromString('pl');
        $locale3 = Locale::ENGLISH;

        $this->assertSame($locale1, $locale2);
        $this->assertNotSame($locale1, $locale3);
    }

    public function testItReturnsAllLocaleCodes(): void
    {
        $codes = Locale::codes();

        $this->assertIsArray($codes);
        $this->assertContains('pl', $codes);
        $this->assertContains('en', $codes);
        $this->assertCount(2, $codes);
    }

    public function testItReturnsDefaultLocale(): void
    {
        $default = Locale::default();

        $this->assertSame(Locale::POLISH, $default);
        $this->assertSame('pl', $default->value);
    }

    public function testItTryFromStringReturnsLocale(): void
    {
        $locale = Locale::tryFromString('en');

        $this->assertNotNull($locale);
        $this->assertSame(Locale::ENGLISH, $locale);
    }

    public function testItTryFromStringReturnsNullForInvalidLocale(): void
    {
        $locale = Locale::tryFromString('invalid');

        $this->assertNull($locale);
    }
}
