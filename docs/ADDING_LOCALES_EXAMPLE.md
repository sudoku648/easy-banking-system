# Example: Adding German Locale

This is a practical example of adding German (de) locale to the Easy Banking System.

## Step 1: Generate Translation Templates

Run the script to generate translation file templates:

```bash
./bin/generate-locale-templates.sh de
```

This creates:
- `translations/app.de.yaml`
- `translations/login.de.yaml`
- `translations/dashboard.de.yaml`
- `translations/bank_account.de.yaml`
- `translations/transaction.de.yaml`
- `translations/flash.de.yaml`

## Step 2: Translate the Files

Edit each generated file and translate the values. Example for `translations/app.de.yaml`:

```yaml
# App translations for locale: de
app:
  name: Easy Banking System  # Usually keep brand names
  copyright: © 2025 Easy Banking System. Alle Rechte vorbehalten.

nav:
  dashboard: Dashboard
  new_account: Neues Konto
  transfer: Überweisung
  history: Historie
  login: Anmelden
  logout: Abmelden

common:
  welcome: Willkommen
  active: Aktiv
  closed: Geschlossen
  status: Status
  actions: Aktionen
  submit: Absenden
  cancel: Abbrechen
  back: Zurück
  yes: Ja
  no: Nein
  account_closed: Konto geschlossen
```

Repeat for all other domains (login, dashboard, bank_account, transaction, flash).

## Step 3: Update Locale Enum

Edit `src/UserManagement/Domain/ValueObject/Locale.php`:

```php
enum Locale: string
{
    case POLISH = 'pl';
    case ENGLISH = 'en';
    case GERMAN = 'de';  // Add this line

    public function isPolish(): bool
    {
        return $this === self::POLISH;
    }

    public function isEnglish(): bool
    {
        return $this === self::ENGLISH;
    }

    public function isGerman(): bool  // Add this helper method (optional)
    {
        return $this === self::GERMAN;
    }

    // ... rest of the methods (fromString, codes, etc. work automatically)
}
```

## Step 4: Update Twig Extension

Edit `src/UserManagement/Symfony/Twig/LocaleExtension.php`:

```php
private const LOCALE_FLAGS = [
    'pl' => '🇵🇱',
    'en' => '🇬🇧',
    'de' => '🇩🇪',  // Add this line
];

private const LOCALE_NAMES = [
    'pl' => 'Polski',
    'en' => 'English',
    'de' => 'Deutsch',  // Add this line
];
```

## Step 5: Create Database Migration

Generate a new migration:

```bash
docker compose -f docker-compose.dev.yaml exec ebs php bin/console doctrine:migrations:generate
```

Edit the generated migration file (e.g., `migrations/Version20251120220000.php`):

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251120220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add German (de) locale to user table CHECK constraint';
    }

    public function up(Schema $schema): void
    {
        // Drop existing constraint
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT IF EXISTS user_locale_check');

        // Add new constraint with German
        $this->addSql('
            ALTER TABLE "user"
            ADD CONSTRAINT user_locale_check
            CHECK (locale IN (\'pl\', \'en\', \'de\'))
        ');
    }

    public function down(Schema $schema): void
    {
        // Revert to previous constraint (without German)
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT user_locale_check');

        $this->addSql('
            ALTER TABLE "user"
            ADD CONSTRAINT user_locale_check
            CHECK (locale IN (\'pl\', \'en\'))
        ');
    }
}
```

Run the migration:

```bash
docker compose -f docker-compose.dev.yaml exec ebs php bin/console doctrine:migrations:migrate --no-interaction
```

## Step 6: Test the Changes

1. **Rebuild containers** (to include new translation files):
   ```bash
   docker compose -f docker-compose.dev.yaml down
   docker compose -f docker-compose.dev.yaml up -d --build
   ```

2. **Clear cache**:
   ```bash
   docker compose -f docker-compose.dev.yaml exec ebs php bin/console cache:clear
   ```

3. **Test manually**:
   - Open http://localhost:8080
   - You should now see 🇩🇪 Deutsch in the language dropdown
   - Click it to switch to German
   - Verify all translations are displayed correctly
   - Log in and verify the language persists

4. **Run automated tests**:
   ```bash
   make test
   ```

## What Happens Automatically

Once you complete steps 1-5 above:

✅ The language dropdown in templates will automatically show German flag and name
✅ The `/change-locale/de` route will work without modification
✅ `Locale::fromString('de')` will work
✅ `Locale::codes()` will include 'de'
✅ The login page language switcher will show German button

## Verification Checklist

- [ ] All 6 translation files created and translated
- [ ] Locale enum has GERMAN case
- [ ] LocaleExtension has German flag (🇩🇪) and name (Deutsch)
- [ ] Database migration created and executed
- [ ] Containers rebuilt
- [ ] Cache cleared
- [ ] Manual testing passed
- [ ] All automated tests pass

## Common Issues

**Issue**: German option doesn't appear in language dropdown
- **Solution**: Make sure you rebuilt Docker containers and cleared cache

**Issue**: Translation keys show instead of German text (e.g., "app.name")
- **Solution**: Verify translation files are in the translations/ directory and have correct YAML syntax

**Issue**: Database error when saving user locale
- **Solution**: Make sure you ran the migration to update the CHECK constraint

**Issue**: `/change-locale/de` gives 404
- **Solution**: Clear cache - the route pattern is generated dynamically from the enum
