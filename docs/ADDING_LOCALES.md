# Adding New Locales to Easy Banking System

This guide explains how to add a new locale (language) to the application.

## Steps to Add a New Locale

### 1. Update the Locale Enum
**File:** `src/UserManagement/Domain/ValueObject/Locale.php`

Add a new case to the enum:
```php
enum Locale: string
{
    case POLISH = 'pl';
    case ENGLISH = 'en';
    case GERMAN = 'de';  // New locale
}
```

### 2. Update Twig Extension Metadata
**File:** `src/UserManagement/Symfony/Twig/LocaleExtension.php`

Add flag and name for the new locale:
```php
private const LOCALE_FLAGS = [
    'pl' => '🇵🇱',
    'en' => '🇬🇧',
    'de' => '🇩🇪',  // New locale flag
];

private const LOCALE_NAMES = [
    'pl' => 'Polski',
    'en' => 'English',
    'de' => 'Deutsch',  // New locale name
];
```

### 3. Create Translation Files
Create new translation files for each domain in `translations/` directory:
- `app.de.yaml` - App-wide translations
- `login.de.yaml` - Login page translations
- `dashboard.de.yaml` - Dashboard translations
- `bank_account.de.yaml` - Bank account translations
- `transaction.de.yaml` - Transaction translations
- `flash.de.yaml` - Flash message translations

You can copy existing files and translate the values:
```bash
# Example for German
cp translations/app.en.yaml translations/app.de.yaml
cp translations/login.en.yaml translations/login.de.yaml
# ... repeat for all domains
```

### 4. Create Database Migration
**Command:** Generate a new migration to update the CHECK constraint:

```bash
docker compose -f docker-compose.dev.yaml exec ebs php bin/console doctrine:migrations:generate
```

Update the generated migration file:
```php
public function up(Schema $schema): void
{
    $this->addSql('
        ALTER TABLE "user"
        DROP CONSTRAINT IF EXISTS user_locale_check;

        ALTER TABLE "user"
        ADD CONSTRAINT user_locale_check
        CHECK (locale IN (\'pl\', \'en\', \'de\'))
    ');
}

public function down(Schema $schema): void
{
    $this->addSql('
        ALTER TABLE "user"
        DROP CONSTRAINT user_locale_check;

        ALTER TABLE "user"
        ADD CONSTRAINT user_locale_check
        CHECK (locale IN (\'pl\', \'en\'))
    ');
}
```

Run the migration:
```bash
docker compose -f docker-compose.dev.yaml exec ebs php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Rebuild and Test
```bash
# Rebuild containers to include new translation files
docker compose -f docker-compose.dev.yaml down
docker compose -f docker-compose.dev.yaml up -d --build

# Clear cache
docker compose -f docker-compose.dev.yaml exec ebs php bin/console cache:clear

# Run tests
make test
```

## What Gets Updated Automatically

When you add a new locale following steps 1-5 above, the following will work automatically:

✅ **Route validation** - The `/change-locale/{locale}` route generates pattern from all enum cases

✅ **Template locale switcher** - Both the navbar dropdown and login page buttons use `available_locales()` Twig function to loop through all available locales

✅ **Locale validation** - The `Locale::fromString()` method uses `Locale::codes()` which automatically includes all enum cases

✅ **Default locale** - Defined in `Locale::default()` method (currently Polish)

## File Checklist

When adding a new locale, you need to modify/create these files:

- [ ] `src/UserManagement/Domain/ValueObject/Locale.php` - Add enum case
- [ ] `src/UserManagement/Symfony/Twig/LocaleExtension.php` - Add flag and name
- [ ] `translations/app.{locale}.yaml` - Create translation file
- [ ] `translations/login.{locale}.yaml` - Create translation file
- [ ] `translations/dashboard.{locale}.yaml` - Create translation file
- [ ] `translations/bank_account.{locale}.yaml` - Create translation file
- [ ] `translations/transaction.{locale}.yaml` - Create translation file
- [ ] `translations/flash.{locale}.yaml` - Create translation file
- [ ] `migrations/VersionXXXXXXXXXXXXXX.php` - Create new migration for CHECK constraint
- [ ] Rebuild Docker containers
- [ ] Clear cache
- [ ] Run tests

## Notes

- The system uses domain-based translation files (prefix+locale format) to organize translations by context
- Each locale needs translation files for all domains (app, login, dashboard, bank_account, transaction, flash)
- The default locale is Polish (`pl`) and is defined in `Locale::default()` method
- Database CHECK constraint must be updated via migration when adding new locales
- Templates automatically adapt to new locales - no template changes required
