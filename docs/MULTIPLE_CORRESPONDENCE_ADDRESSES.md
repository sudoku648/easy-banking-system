# Multiple Correspondence Addresses Feature

## Overview
This document describes the implementation of multiple correspondence addresses for customers. When the "same as permanent residence" checkbox is unchecked, users can add multiple correspondence addresses dynamically through the UI.

## Key Features

1. **Dynamic Address Collection**: Users can add/remove multiple correspondence addresses via JavaScript
2. **Smart Checkbox Behavior**: When checked, correspondence address fields are hidden AND disabled (fields not sent to backend)
3. **Form Validation**: Each correspondence address is validated individually
4. **Database Support**: Customer can have multiple correspondence addresses stored separately

## Architecture

### Domain Layer

#### `CreateCustomerCommand`
```php
public function __construct(
    // ... personal info fields ...
    public array $correspondenceAddresses, // Array of address data
)
```

The `correspondenceAddresses` parameter is an array of associative arrays with structure:
```php
[
    ['street' => string, 'city' => string, 'postalCode' => string, 'country' => string],
    ['street' => string, 'city' => string, 'postalCode' => string, 'country' => string],
    // ... more addresses
]
```

#### `CreateCustomerCommandHandler`
- Takes first correspondence address for `Customer::create()`
- Iterates remaining addresses and uses `Customer::addCorrespondenceAddress()`
- All addresses are saved in a single transaction

#### `Customer` Entity
Already supported multiple addresses:
- `create()` - requires at least one correspondence address
- `addCorrespondenceAddress()` - adds additional correspondence addresses
- `getCorrespondenceAddresses()` - returns all correspondence addresses

### Presentation Layer

#### New DTO: `CorrespondenceAddressDto`
```php
final class CorrespondenceAddressDto
{
    public ?string $street = null;
    public ?string $city = null;
    public ?string $postalCode1 = null;  // First part: XX
    public ?string $postalCode2 = null;  // Second part: XXX
    public ?string $country = null;
}
```

#### Updated DTO: `OpenAccountNewCustomerDto`
```php
final class OpenAccountNewCustomerDto
{
    // ... existing fields ...
    public bool $sameAsPermament = false;

    /**
     * @var CorrespondenceAddressDto[]
     */
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'At least one correspondence address is required')]
    public array $correspondenceAddresses = [];

    public function __construct()
    {
        // Initialize with one empty correspondence address
        $this->correspondenceAddresses = [new CorrespondenceAddressDto()];
    }
}
```

#### New Form: `CorrespondenceAddressFormType`
Embedded form for single correspondence address with:
- Street (TextType)
- City (TextType)
- Postal Code Part 1 (TextType, maxlength: 2)
- Postal Code Part 2 (TextType, maxlength: 3)
- Country (ChoiceType with dropdown)

#### Updated Form: `OpenAccountNewCustomerFormType`
```php
->add('correspondenceAddresses', CollectionType::class, [
    'entry_type' => CorrespondenceAddressFormType::class,
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
])
```

#### Controller Logic: `EmployeeOpenAccountNewCustomerController`
```php
// Prepare correspondence addresses
$correspondenceAddresses = [];

if ($dto->sameAsPermament) {
    // Use permanent address as single correspondence address
    $correspondenceAddresses[] = [
        'street' => $dto->permanentResidenceStreet,
        'city' => $dto->permanentResidenceCity,
        'postalCode' => $permanentPostalCode,
        'country' => $dto->permanentResidenceCountry,
    ];
} else {
    // Process all correspondence addresses from collection
    foreach ($dto->correspondenceAddresses as $addressDto) {
        $correspondenceAddresses[] = [
            'street' => $addressDto->street,
            'city' => $addressDto->city,
            'postalCode' => $addressDto->postalCode1 . '-' . $addressDto->postalCode2,
            'country' => $addressDto->country,
        ];
    }
}
```

### Template Layer

#### Template Structure
```twig
<div id="correspondence-address-group">
    <div id="correspondence-addresses-container">
        {% for addressForm in form.correspondenceAddresses %}
            <div class="correspondence-address-item border rounded p-3 mb-3">
                {# Address fields #}
                {% if loop.index > 1 %}
                    <button type="button" class="remove-address-btn">Remove</button>
                {% endif %}
            </div>
        {% endfor %}
    </div>

    {# Hidden prototype for JavaScript #}
    <div id="address-prototype" style="display: none;">
        {# Prototype address form with __name__ placeholders #}
    </div>

    <button type="button" id="add-correspondence-address">
        Add Correspondence Address
    </button>
</div>
```

#### JavaScript Behavior

**Checkbox Toggle**:
```javascript
function toggleCorrespondenceAddress() {
    if (checkbox.checked) {
        correspondenceGroup.style.display = 'none';
        // IMPORTANT: Disable all fields so they're not sent to backend
        const inputs = correspondenceGroup.querySelectorAll('input, select');
        inputs.forEach(input => input.disabled = true);
    } else {
        correspondenceGroup.style.display = 'block';
        // Re-enable all fields
        const inputs = correspondenceGroup.querySelectorAll('input, select');
        inputs.forEach(input => input.disabled = false);
    }
}
```

**Add Address**:
```javascript
addButton.addEventListener('click', function() {
    const index = parseInt(addressesContainer.dataset.index);
    const prototypeElement = document.getElementById('address-prototype');

    // Clone prototype
    const newAddress = prototypeElement.firstElementChild.cloneNode(true);

    // Replace __name__ with index
    newAddress.innerHTML = newAddress.innerHTML.replace(/__name__/g, index);

    // Add remove button listener
    const removeBtn = newAddress.querySelector('.remove-address-btn');
    removeBtn.addEventListener('click', () => newAddress.remove());

    addressesContainer.appendChild(newAddress);
    addressesContainer.dataset.index = index + 1;
});
```

**Remove Address**:
```javascript
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-address-btn')) {
        e.target.closest('.correspondence-address-item').remove();
    }
});
```

## Translations

### English (`bank_account.en.yaml`)
```yaml
bank_account.add_correspondence_address: Add Correspondence Address
bank_account.remove_address: Remove
```

### Polish (`bank_account.pl.yaml`)
```yaml
bank_account.add_correspondence_address: Dodaj adres korespondencyjny
bank_account.remove_address: Usuń
```

## Testing

### Unit Tests Updated
- `CreateCustomerCommandHandlerTest.php` - Updated to use array of correspondence addresses

### Integration Points to Test
1. ✅ Create customer with single correspondence address
2. ✅ Create customer with multiple correspondence addresses
3. ✅ Create customer with checkbox checked (same as permanent)
4. ✅ Form validation for each address
5. ⚠️ JavaScript add/remove functionality (manual browser testing required)
6. ⚠️ Disabled fields not sent when checkbox checked (manual browser testing required)

## User Flow

### Scenario 1: Same as Permanent Address
1. User checks "Correspondence address is same as permanent residence"
2. All correspondence address fields disappear
3. Fields are disabled (not sent to backend)
4. Backend creates one correspondence address identical to permanent address

### Scenario 2: Single Different Address
1. User leaves checkbox unchecked
2. One correspondence address form is visible (initialized by default)
3. User fills in different address
4. Backend creates one correspondence address with provided data

### Scenario 3: Multiple Addresses
1. User leaves checkbox unchecked
2. User fills first correspondence address
3. User clicks "Add Correspondence Address"
4. New address form appears with remove button
5. User fills second address
6. Can continue adding more addresses
7. Backend creates multiple correspondence addresses

### Scenario 4: Remove Address
1. User has multiple addresses
2. User clicks remove button on any address (except first)
3. Address form is removed from DOM
4. Backend only receives remaining addresses

## Database Schema
No changes required - existing `customer_address` table already supports multiple correspondence addresses:
- `customer_id` + `type` unique constraint allows one permanent, multiple correspondence
- Each correspondence address has its own row with `type = 'CORRESPONDENCE'`

## Important Implementation Details

### Field Disabling vs Hiding
When checkbox is checked, fields are BOTH hidden AND disabled:
- **Hidden**: For better UX (cleaner form)
- **Disabled**: To prevent fields from being submitted to backend (security + data integrity)

### Postal Code Handling
- Form has two separate fields: `postalCode1` (XX) and `postalCode2` (XXX)
- Controller concatenates with dash: `$postalCode1 . '-' . $postalCode2`
- Stored in database as single string: `XX-XXX`
- Validated with regex pattern in DTO

### Array Reindexing
When working with `Customer::getCorrespondenceAddresses()`, use `array_values()` if you need sequential numeric keys:
```php
$correspondenceAddresses = array_values($customer->getCorrespondenceAddresses());
// Now safe to use $correspondenceAddresses[0], $correspondenceAddresses[1], etc.
```

### Form Collection Type
Symfony's `CollectionType` with `allow_add: true` and `allow_delete: true` enables dynamic forms, but requires JavaScript to actually add/remove entries in the UI.

## Files Modified

### Created
- `src/BankAccount/Presentation/Dto/CorrespondenceAddressDto.php`
- `src/BankAccount/Presentation/Form/CorrespondenceAddressFormType.php`
- `docs/MULTIPLE_CORRESPONDENCE_ADDRESSES.md`

### Modified
- `src/BankAccount/Presentation/Dto/OpenAccountNewCustomerDto.php`
- `src/BankAccount/Presentation/Form/OpenAccountNewCustomerFormType.php`
- `src/BankAccount/Presentation/Controller/EmployeeOpenAccountNewCustomerController.php`
- `src/UserManagement/Application/Command/CreateCustomerCommand.php`
- `src/UserManagement/Application/Command/CreateCustomerCommandHandler.php`
- `templates/bank_account/open_new_customer.html.twig`
- `translations/bank_account.en.yaml`
- `translations/bank_account.pl.yaml`
- `tests/Unit/UserManagement/Application/Command/CreateCustomerCommandHandlerTest.php`

## Next Steps

1. ✅ Unit tests passing
2. ⚠️ Manual browser testing required:
   - Test add/remove address functionality
   - Verify checkbox hides AND disables fields
   - Test form submission with multiple addresses
   - Test validation errors on multiple addresses
3. ⏳ Update fixture generator if needed (currently generates 1 correspondence address)
4. ⏳ Update remaining test files that create customers (24 files found previously)

## Known Limitations

1. No minimum/maximum limit on correspondence addresses (could add if needed)
2. First correspondence address cannot be removed (enforced in template with `{% if loop.index > 1 %}`)
3. Address prototype styling relies on Bootstrap classes
4. No confirmation dialog when removing addresses
