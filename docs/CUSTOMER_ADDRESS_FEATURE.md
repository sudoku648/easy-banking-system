# Customer Address Feature Implementation

## Overview
This implementation adds comprehensive address data collection for customers, including:
- **Permanent Residence Address** (required)
- **Correspondence Addresses** (optional, one or more)
- Support for a checkbox "correspondence address is same as permanent residence"

## Database Schema

### New Table: `customer_address`
```sql
CREATE TABLE customer_address (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    customer_id UUID NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    type TEXT NOT NULL CHECK (type IN ('PERMANENT_RESIDENCE', 'CORRESPONDENCE')),
    street TEXT NOT NULL CHECK (char_length(street) BETWEEN 3 AND 100),
    city TEXT NOT NULL CHECK (char_length(city) BETWEEN 2 AND 50),
    postal_code TEXT NOT NULL CHECK (postal_code ~ '^[0-9]{2}-[0-9]{3}$'),
    country TEXT NOT NULL CHECK (char_length(country) BETWEEN 2 AND 50),
    UNIQUE(customer_id, type) DEFERRABLE INITIALLY DEFERRED
);
```

**Migration:** `Version20251202000000.php`

## Domain Model

### Value Objects
- `Address` - Composite value object containing street, city, postal code, and country
- `Street` - Street name (3-100 characters)
- `City` - City name (2-50 characters)
- `PostalCode` - Polish postal code format (XX-XXX)
- `Country` - Country name (2-50 characters)
- `AddressId` - UUID identifier for addresses
- `AddressType` - Enum: `PERMANENT_RESIDENCE` or `CORRESPONDENCE`

### Entity
- `CustomerAddress` - Entity representing a customer's address with type

### Customer Entity Updates
The `Customer` entity now:
- Requires a permanent residence address in the `create()` method
- Can have multiple correspondence addresses via `addCorrespondenceAddress()`
- Provides methods:
  - `getPermanentResidenceAddress(): ?CustomerAddress`
  - `getCorrespondenceAddresses(): array`
  - `getAllAddresses(): array`

## Application Layer

### Updated Command
`CreateCustomerCommand` now includes:
- `permanentResidenceStreet`
- `permanentResidenceCity`
- `permanentResidencePostalCode`
- `permanentResidenceCountry`
- `correspondenceAddresses` (array of address data)

### Updated Handler
`CreateCustomerCommandHandler` now:
1. Creates the permanent residence address
2. Creates the customer with the permanent address
3. Adds any correspondence addresses provided
4. Saves everything via the repository

## Infrastructure Layer

### Repository Updates
`DbalUserRepository` now:
- Saves customer addresses when saving a customer
- Loads customer addresses when retrieving a customer
- Manages the relationship between customers and their addresses

### Fixtures
`CustomerFixture` updated to:
- Create a permanent residence address for each customer
- Create a correspondence address for 40% of customers (different from permanent)

## CLI Command

### New Command: `app:create-customer`
```bash
php bin/console app:create-customer \
    "John" "Doe" "john.doe" "password123" \
    "Main Street 123" "Warsaw" "00-001" "Poland" \
    --correspondence="Office Street 456,Krakow,30-001,Poland"
```

## Testing

### Test Helper
- `AddressTestHelper` trait - Provides `createTestAddress()` method for tests

### Updated Test Files
All test files creating `Customer` instances have been updated:
- `CustomerTest.php`
- `ChangePasswordTest.php`
- `DbalUserRepositoryTest.php`
- `DbalTransactionRepositoryTest.php`
- `DbalBankAccountRepositoryTest.php`
- `CloseBankAccountWithBalanceTest.php`
- `ApiTestCase.php`

### New Test Files
- `AddressTest.php` - Tests for Address value object
- `PostalCodeTest.php` - Tests for PostalCode validation

## Usage Example

### Creating a Customer with Addresses

```php
use App\UserManagement\Application\Command\CreateCustomerCommand;

// Create customer with permanent address only
$command = new CreateCustomerCommand(
    username: 'john.doe',
    password: 'SecurePass123',
    firstName: 'John',
    lastName: 'Doe',
    permanentResidenceStreet: 'Main Street 123',
    permanentResidenceCity: 'Warsaw',
    permanentResidencePostalCode: '00-001',
    permanentResidenceCountry: 'Poland',
);

// Create customer with permanent and correspondence addresses
$command = new CreateCustomerCommand(
    username: 'jane.smith',
    password: 'SecurePass456',
    firstName: 'Jane',
    lastName: 'Smith',
    permanentResidenceStreet: 'Home Street 456',
    permanentResidenceCity: 'Krakow',
    permanentResidencePostalCode: '30-001',
    permanentResidenceCountry: 'Poland',
    correspondenceAddresses: [
        [
            'street' => 'Office Street 789',
            'city' => 'Gdansk',
            'postalCode' => '80-001',
            'country' => 'Poland',
        ],
    ],
);
```

### Using the "Same as Permanent" Logic
When implementing a form:
- If checkbox "correspondence address is same as permanent residence" is checked
- Do NOT add the permanent address data to `correspondenceAddresses` array
- The system will only store the permanent residence address

If the checkbox is NOT checked:
- Add the correspondence address data to `correspondenceAddresses` array
- The system will store both addresses separately

## Form Implementation

### Updated: Employee Open Account for New Customer
The `EmployeeOpenAccountNewCustomerController` and related form have been updated to collect address data:

**Updated Files:**
- `OpenAccountNewCustomerDto` - Added street, city, postalCode, country fields with validation
- `OpenAccountNewCustomerFormType` - Added form fields for address data
- `EmployeeOpenAccountNewCustomerController` - Updated to pass address data to CreateCustomerCommand
- `translations/bank_account.en.yaml` - Added English translations for address fields
- `translations/bank_account.pl.yaml` - Added Polish translations for address fields

### Next Steps for Customer Self-Registration (Optional)

To add a customer self-registration form, you would need to:

1. **Create a Form Type** (`CustomerRegistrationFormType.php`):
   - Fields for personal data (username, password, first name, last name)
   - Fields for permanent address (street, city, postal code, country)
   - Checkbox: "Correspondence address is same as permanent residence"
   - Conditional fields for correspondence address (shown when checkbox unchecked)

2. **Create a Registration Controller**:
   - Handle form submission
   - Process the checkbox logic
   - Dispatch `CreateCustomerCommand`

3. **Create Twig Templates**:
   - Registration form template
   - Address input partials
   - JavaScript for showing/hiding correspondence address fields

4. **Add Translation Keys**:
   - Form labels and error messages in `translations/`

## Files Modified/Created

### Domain Layer
- `src/UserManagement/Domain/ValueObject/Address.php` (new)
- `src/UserManagement/Domain/ValueObject/Street.php` (new)
- `src/UserManagement/Domain/ValueObject/City.php` (new)
- `src/UserManagement/Domain/ValueObject/PostalCode.php` (new)
- `src/UserManagement/Domain/ValueObject/Country.php` (new)
- `src/UserManagement/Domain/ValueObject/AddressId.php` (new)
- `src/UserManagement/Domain/ValueObject/AddressType.php` (new)
- `src/UserManagement/Domain/Entity/CustomerAddress.php` (new)
- `src/UserManagement/Domain/Entity/Customer.php` (modified)

### Application Layer
- `src/UserManagement/Application/Command/CreateCustomerCommand.php` (modified)
- `src/UserManagement/Application/Command/CreateCustomerCommandHandler.php` (modified)

### Infrastructure Layer
- `src/UserManagement/Infrastructure/Persistence/Repository/DbalUserRepository.php` (modified)
- `src/UserManagement/Infrastructure/Fixtures/CustomerFixture.php` (modified)
- `migrations/Version20251202000000.php` (new)

### CLI Layer
- `src/UserManagement/Cli/CreateCustomerConsoleCommand.php` (new)

### Tests
- `tests/Support/AddressTestHelper.php` (new)
- `tests/Unit/UserManagement/Domain/Entity/CustomerTest.php` (modified)
- `tests/Unit/UserManagement/Domain/Entity/ChangePasswordTest.php` (modified)
- `tests/Unit/UserManagement/Domain/ValueObject/AddressTest.php` (new)
- `tests/Unit/UserManagement/Domain/ValueObject/PostalCodeTest.php` (new)
- Multiple integration and presentation test files (modified)

## Running Migrations

```bash
# Development environment
make migrate

# Test environment
make migrate-test

# Or using docker
docker exec easy-banking-service-ebs php bin/console doctrine:migrations:migrate --no-interaction
```

## Running Tests

```bash
# All tests
make test

# Unit tests only
make test --testsuite=unit

# Integration tests only
make test --testsuite=integration
```
