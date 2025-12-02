# Customer Address Feature - Final Implementation Summary

## Changes Made

### 1. Domain Model Updates

**Customer Entity:**
- Now requires BOTH permanent residence AND correspondence address in `create()` method
- Correspondence address is mandatory but can be same as permanent residence

**Command Update:**
```php
CreateCustomerCommand(
    string $username,
    string $password,
    string $firstName,
    string $lastName,
    string $permanentResidenceStreet,
    string $permanentResidenceCity,
    string $permanentResidencePostalCode,  // Full format XX-XXX
    string $permanentResidenceCountry,
    string $correspondenceStreet,
    string $correspondenceCity,
    string $correspondencePostalCode,      // Full format XX-XXX
    string $correspondenceCountry,
)
```

### 2. Presentation Layer - Form Features

**Split Postal Code Fields:**
- Two separate input fields with dash between them
- First field: 2 digits (XX)
- Second field: 3 digits (XXX)
- Validates each part separately

**Country as Select Dropdown:**
- Predefined list of countries
- Poland, Germany, France, UK, Spain, Italy, Netherlands, Other

**Address Grouping:**
- Personal Information section
- Permanent Residence Address (in card)
- Correspondence Address (in card)
- Account Settings section

**Checkbox Feature:**
- "Correspondence address is same as permanent residence"
- When checked: hides correspondence address fields
- When unchecked: shows correspondence address fields
- JavaScript toggles visibility dynamically

### 3. DTO Structure

```php
class OpenAccountNewCustomerDto
{
    // Personal info
    public ?string $username = null;
    public ?string $password = null;
    public ?string $firstName = null;
    public ?string $lastName = null;

    // Permanent residence (split postal code)
    public ?string $permanentResidenceStreet = null;
    public ?string $permanentResidenceCity = null;
    public ?string $permanentResidencePostalCode1 = null;  // XX
    public ?string $permanentResidencePostalCode2 = null;  // XXX
    public ?string $permanentResidenceCountry = null;

    // Checkbox
    public bool $sameAsPermament = false;

    // Correspondence address (split postal code)
    public ?string $correspondenceStreet = null;
    public ?string $correspondenceCity = null;
    public ?string $correspondencePostalCode1 = null;  // XX
    public ?string $correspondencePostalCode2 = null;  // XXX
    public ?string $correspondenceCountry = null;

    // Account
    public ?string $currency = null;
}
```

### 4. Controller Logic

```php
// Combine postal code parts
$permanentPostalCode = $dto->permanentResidencePostalCode1 . '-' . $dto->permanentResidencePostalCode2;

// Handle checkbox - copy permanent to correspondence if checked
if ($dto->sameAsPermament) {
    $correspondenceStreet = $dto->permanentResidenceStreet;
    $correspondenceCity = $dto->permanentResidenceCity;
    $correspondencePostalCode = $permanentPostalCode;
    $correspondenceCountry = $dto->permanentResidenceCountry;
} else {
    $correspondenceStreet = $dto->correspondenceStreet;
    $correspondenceCity = $dto->correspondenceCity;
    $correspondencePostalCode = $dto->correspondencePostalCode1 . '-' . $dto->correspondencePostalCode2;
    $correspondenceCountry = $dto->correspondenceCountry;
}
```

### 5. Template Features

- Grouped sections with headers and cards
- Inline postal code fields with dash separator
- Dynamic JavaScript toggle for correspondence address visibility
- Bootstrap styling for responsive layout

### 6. Translations

Added keys for:
- `bank_account.personal_info`
- `bank_account.account_settings`
- `bank_account.permanent_residence`
- `bank_account.permanent_residence_street/city/postal_code/country`
- `bank_account.correspondence`
- `bank_account.correspondence_street/city/postal_code/country`
- `bank_account.same_as_permanent`
- `bank_account.placeholder_select_country`

Available in English and Polish.

### 7. Test Updates Required

**All test files creating Customer instances need to add correspondence address:**

```php
// OLD
Customer::create($id, $username, $password, $firstName, $lastName, $address);

// NEW
Customer::create(
    $id,
    $username,
    $password,
    $firstName,
    $lastName,
    $permanentAddress,
    $correspondenceAddress, // <-- ADD THIS
);
```

**Files needing updates:**
1. `tests/Unit/UserManagement/Domain/Entity/CustomerTest.php` (6 places)
2. `tests/Unit/UserManagement/Domain/Entity/ChangePasswordTest.php` (4 places)
3. `tests/Api/ApiTestCase.php` (2 places)
4. `tests/Presentation/PresentationTestCase.php` (2 places)
5. `tests/Integration/UserManagement/Infrastructure/Persistence/Repository/DbalUserRepositoryTest.php` (6 places)
6. `tests/Integration/Transaction/Infrastructure/Persistence/Repository/DbalTransactionRepositoryTest.php` (1 place)
7. `tests/Integration/BankAccount/Infrastructure/Persistence/Repository/DbalBankAccountRepositoryTest.php` (1 place)
8. `tests/Integration/BankAccount/Application/Command/CloseBankAccountWithBalanceTest.php` (1 place)
9. `tests/Unit/UserManagement/Application/Command/CreateCustomerCommandHandlerTest.php` (update test assertions)

**Helper available in AddressTestHelper trait:**
```php
$this->createTestCorrespondenceAddress()
```

### 8. Database

- Migration already supports the structure (UNIQUE constraint on customer_id + type)
- Fixtures updated to always create both addresses (60% same, 40% different)

## Testing the Implementation

1. **Run migrations:**
```bash
make migrate
make migrate-test
```

2. **Update test files** (add correspondence address parameter to all Customer::create calls)

3. **Run tests:**
```bash
make test
```

4. **Test in browser:**
- Log in as employee
- Navigate to "Open Account for New Customer"
- Fill in the form with grouped address sections
- Test checkbox to toggle correspondence address
- Verify split postal code fields work correctly
- Verify country dropdown works

## Key Implementation Points

✅ **Permanent residence address:** Required, always collected
✅ **Correspondence address:** Required, but can be same as permanent
✅ **Checkbox:** "Same as permanent" - copies data automatically
✅ **Split postal code:** Two fields with dash (XX-XXX format)
✅ **Country select:** Dropdown with predefined countries
✅ **Visual grouping:** Cards and sections for better UX
✅ **Bilingual:** Full English and Polish translations
✅ **JavaScript:** Dynamic show/hide of correspondence fields

## Next Steps

Update all test files to include the correspondence address parameter when creating Customer instances. Use the helper method `$this->createTestCorrespondenceAddress()` for convenience.
