# Fixtures Documentation

## Overview

The fixtures system provides a convenient way to populate the development database with realistic sample data. This is useful for:
- Local development and testing
- Demonstrating application features
- Manual testing of UI and business logic

## Architecture

The fixtures system follows the hexagonal architecture pattern:

```
src/
├── Shared/
│   ├── Infrastructure/Fixtures/
│   │   ├── FixtureInterface.php       # Contract for all fixtures
│   │   ├── AbstractFixture.php        # Base class with Faker support
│   │   └── FixtureLoader.php          # Orchestrates fixture loading
│   └── Cli/
│       └── LoadFixturesConsoleCommand.php
├── UserManagement/Infrastructure/Fixtures/
│   ├── EmployeeFixture.php            # Creates employee accounts
│   └── CustomerFixture.php            # Creates customer accounts
├── BankAccount/Infrastructure/Fixtures/
│   └── BankAccountFixture.php         # Creates bank accounts
└── Transaction/Infrastructure/Fixtures/
    └── TransactionFixture.php         # Creates transactions
```

## Loading Fixtures

### Using Make Command (Recommended)

```bash
make fixtures
```

This command will:
1. Purge all existing data from the database
2. Load fixtures in the correct order
3. Display a summary of created entities

### Using Console Command Directly

```bash
docker compose -f docker-compose.dev.yaml exec ebs php bin/console app:fixtures:load --purge --no-interaction
```

### Options

- `--purge`: Purge all data before loading fixtures (recommended)
- `--no-interaction`: Skip confirmation prompt (useful for automation)

Without `--no-interaction`, you'll be asked to confirm before purging data.

## Generated Data

### Employees (3)
Predefined employees with usernames:
- `john.smith` - John Smith
- `anna.kowalska` - Anna Kowalska
- `michael.brown` - Michael Brown

**Default password:** `password123`

### Customers (10)
Randomly generated customers with:
- Random first and last names (using Faker)
- Username format: `firstname.lastname` (e.g., `chris.bergstrom`)
- 90% are active, 10% inactive
- Unique usernames with automatic collision resolution

**Default password:** `password123`

### Bank Accounts (1-3 per customer)
Each customer gets 1-3 bank accounts with:
- Valid Polish IBANs (PL + check digits + 26-digit account number)
- Random currency: PLN or EUR
- Random balance: 0 to 100,000 (in major units)
- 95% are active, 5% inactive
- Unique IBAN generation

### Transactions (3-15 per account)
Random transactions for active accounts:
- Types: CASH_DEPOSIT, CASH_WITHDRAWAL, TRANSFER_DEPOSIT, TRANSFER_WITHDRAWAL
- Random amounts: 1.00 to 500.00 (in major units)
- Random dates within the last 90 days
- Proper exchange rate handling (currently 1:1 for simplicity)

## Fixture Order

Fixtures are loaded in a specific order defined by the `getOrder()` method:

1. **Order 10**: Employee Fixture
2. **Order 20**: Customer Fixture
3. **Order 30**: Bank Account Fixture
4. **Order 40**: Transaction Fixture

This ensures referential integrity (e.g., customers must exist before bank accounts).

## Technical Details

### Dependencies
- **FakerPHP**: Used to generate realistic fake data
- **Doctrine DBAL**: Direct database access for performance
- **Symfony Console**: Command-line interface

### Implementation Pattern

Each fixture extends `AbstractFixture` which provides:
- Doctrine DBAL Connection
- Faker Generator instance
- Implements `FixtureInterface`

```php
final class CustomerFixture extends AbstractFixture
{
    public function load(): void
    {
        // Insert data directly using DBAL
        $this->connection->insert('table_name', $data, $types);
    }

    public function getOrder(): int
    {
        return 20; // Load after employees (10)
    }
}
```

## Creating Custom Fixtures

To add a new fixture:

1. **Create the fixture class:**
```php
<?php

declare(strict_types=1);

namespace App\YourContext\Infrastructure\Fixtures;

use App\Shared\Infrastructure\Fixtures\AbstractFixture;

final class YourFixture extends AbstractFixture
{
    public function load(): void
    {
        echo "Loading your entities...\n";
        
        // Your fixture logic here
        
        echo "✓ Created X entities\n";
    }

    public function getOrder(): int
    {
        return 50; // After transactions
    }
}
```

2. **Register in services.yaml:**
```yaml
App\YourContext\Infrastructure\Fixtures\:
    resource: '../src/YourContext/Infrastructure/Fixtures/'
    tags: ['app.fixture']
```

3. **Run fixtures:**
```bash
make fixtures
```

## Best Practices

1. **Use Faker for realistic data:** Generates names, dates, numbers, etc.
2. **Handle uniqueness:** Check for duplicates (IBANs, usernames) before inserting
3. **Respect dependencies:** Set appropriate order values
4. **Use constants:** Define MIN/MAX values as class constants
5. **Provide feedback:** Echo progress messages during loading
6. **Idempotency:** Fixtures should work when run multiple times with `--purge`

## Environment Considerations

**Important:** Fixtures are designed for **development environment only**.

- Never run fixtures in production
- Consider adding environment checks if needed
- The `--purge` option is destructive - use with caution

## Future Enhancements

Possible improvements:
- Add more realistic transaction patterns (paired transfers)
- Multi-currency exchange rates
- Configurable fixture counts via parameters
- Fixture profiles (small/medium/large datasets)
- Import from CSV/JSON files
- Fixture dependencies and relationships
