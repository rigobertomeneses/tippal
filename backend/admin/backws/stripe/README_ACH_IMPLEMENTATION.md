# Stripe ACH Bank Account Implementation Guide

## Overview
This document describes the implementation of Stripe ACH bank account management for withdrawals/deposits in the application.

## Important Notes
- **Stripe React Native SDK Limitation**: The Stripe React Native SDK does NOT support ACH bank accounts directly
- **Solution**: All bank account tokenization must be done server-side in PHP
- **Database**: Uses soft delete pattern with `deleted_at` timestamps

## Backend Files Structure

### 1. save-bank-account.php
**Purpose**: Creates and saves bank accounts to Stripe and database

**Key Implementation Details**:
```php
// Create bank account token server-side (React Native SDK doesn't support this)
$token = \Stripe\Token::create([
    'bank_account' => [
        'country' => 'US',
        'currency' => 'usd',
        'account_holder_name' => $account_holder_name,
        'account_holder_type' => $account_holder_type,
        'routing_number' => $routing_number,
        'account_number' => $account_number,
    ],
]);

// Attach to customer
$bankAccount = \Stripe\Customer::createSource(
    $stripe_customer_id,
    ['source' => $token->id]
);
```

**Special Features**:
- Handles duplicate accounts by checking `last4` and `routing_number`
- Reactivates soft-deleted accounts instead of creating duplicates
- Creates Stripe customer if doesn't exist
- Uses `date('Y-m-d H:i:s')` directly for timestamps (not formatoFechaHoraBd)

### 2. get-bank-accounts.php
**Purpose**: Retrieves active bank accounts for a user

**Key SQL Filter**:
```sql
AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
```

### 3. delete-bank-account.php
**Purpose**: Soft deletes bank accounts and removes from Stripe

**Key Implementation Details**:
```php
// Delete from Stripe using static method
\Stripe\Customer::deleteSource(
    $stripe_customer_id,  // Customer ID
    $stripe_bank_account_id  // Source ID to delete
);

// Soft delete in database
$fechaactual = date('Y-m-d H:i:s');
$conexion->doUpdate(
    "usuario_bank_accounts",
    "deleted_at = '".$fechaactual."'",
    "bank_account_id = '$bank_account_id'"
);
```

**Special Features**:
- Handles default account reassignment
- Continues with DB deletion even if Stripe deletion fails
- Checks if Stripe SDK is available before attempting Stripe operations

### 4. verify-bank-account.php
**Purpose**: Verifies bank accounts with micro-deposits (if implemented)

## Database Schema

### Table: usuario_bank_accounts
```sql
- bank_account_id (PK)
- usuario_id
- stripe_customer_id
- stripe_bank_account_id
- account_holder_name
- account_holder_type (default: 'individual')
- account_type (checking/savings)
- bank_name
- last4
- routing_number
- fingerprint (Stripe's unique identifier)
- currency (default: 'usd')
- country (default: 'US')
- is_default (0/1)
- status (new/verified/verification_failed)
- compania_id
- created_at
- updated_at
- deleted_at (NULL for active accounts)
- verified_at
```

### Table: usuario_stripe_customers
```sql
- usuario_id
- stripe_customer_id
- compania_id
- created_at
```

## Frontend Implementation (React Native)

### MyBankAccount.jsx
**Key Points**:
- Cannot use Stripe SDK for bank accounts
- Sends raw bank details to backend for server-side tokenization
- Form validation with Yup
- Email auto-populates from logged-in user
- Form stays empty except email field

**Example Request**:
```javascript
const response = await axios.post(APP_URLAPI + 'stripe/save-bank-account.php', {
    usuario_id: usuario?.valores?.id,
    email: values.email,
    account_holder_name: values.accountHolderName,
    account_holder_type: 'individual',
    account_type: values.accountType,
    account_number: values.bankAccountNumber,
    routing_number: values.routingNumber,
    is_default: useDefaultMethod ? 1 : 0,
    compania: COMPANIA_ID
});
```

## Common Issues & Solutions

### Issue 1: Error 500 when deleting accounts
**Cause**: funciones.php include with relative paths
**Solution**: Define functions locally or use absolute paths

### Issue 2: MySQL datetime errors
**Cause**: Empty or incorrectly formatted timestamps
**Solution**: Use `date('Y-m-d H:i:s')` directly, proper string concatenation

### Issue 3: Stripe delete method error
**Cause**: Wrong API method or syntax
**Solution**: Use `\Stripe\Customer::deleteSource($customer_id, $source_id)`

### Issue 4: Duplicate bank accounts
**Solution**: Check for existing accounts before creating:
```php
$arrExistingLocal = $conexion->doSelect(
    "bank_account_id, deleted_at",
    "usuario_bank_accounts",
    "usuario_id = '$usuario_id' AND last4 = '$last4' AND routing_number = '$routing_number'"
);
```

## Testing Considerations

### Test Mode
- Uses `habilitardemo` variable to detect test mode
- Different Stripe keys for test/live modes stored in `compania` table

### Test Data
- Use Stripe test routing numbers:
  - 110000000 - Valid routing number
  - 000111111 - Will fail verification

## Security Considerations

1. **Never expose Stripe Secret Key** to frontend
2. **Server-side tokenization only** for bank accounts
3. **Validate all inputs** before processing
4. **Use parameterized queries** where possible
5. **Soft delete** preserves audit trail
6. **PCI Compliance**: Never log full account numbers

## API Response Codes

- **0**: Success
- **101**: Unauthorized/Method not allowed
- **102**: Missing required fields
- **106**: Resource not found
- **107**: Duplicate resource
- **108**: Stripe API error
- **109**: General error
- **500**: Server error

## Deployment Checklist

- [ ] Verify Stripe keys are set in `compania` table
- [ ] Ensure vendor/autoload.php includes Stripe SDK
- [ ] Database tables created with proper schema
- [ ] CORS headers configured
- [ ] Error logging enabled for debugging
- [ ] SSL certificate active (required for production)

## Future Enhancements

1. Implement micro-deposit verification flow
2. Add webhook handlers for verification status updates
3. Support for multiple currencies
4. Implement account update functionality
5. Add bank account validation before submission