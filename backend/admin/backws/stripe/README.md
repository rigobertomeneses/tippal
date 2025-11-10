# Stripe Connect Implementation

This directory contains the Stripe Connect implementation for processing payments with destination charges.

## Setup

### 1. Database Setup

Run the SQL script to create necessary tables:

```bash
mysql -u your_username -p your_database < stripe_connect_tables.sql
```

### 2. Configure Stripe Keys

Update your company's Stripe configuration in the database:

```sql
UPDATE compania SET 
  stripe_secret_key = 'sk_test_YOUR_SECRET_KEY',
  stripe_publishable_key = 'pk_test_YOUR_PUBLISHABLE_KEY',
  stripe_connect_enabled = 1,
  stripe_webhook_secret = 'whsec_YOUR_WEBHOOK_SECRET'
WHERE id = 468; -- Your company ID
```

### 3. Install Stripe PHP SDK

Make sure Stripe PHP SDK is installed via Composer:

```bash
cd /path/to/gestiongo/admin
composer require stripe/stripe-php
```

## API Endpoints

### 1. Create Payment Intent
**Endpoint:** `/admin/backws/stripe/create-payment-intent.php`

**Method:** POST

**Parameters:**
- `amount` (required): Amount in cents
- `currency`: Currency code (default: usd)
- `compania` (required): Company ID
- `usuario_id` (required): User ID making the payment
- `destination_account_id`: User ID receiving the payment (for Connect)
- `application_fee_amount`: Platform fee in cents

**Response:**
```json
{
  "code": 0,
  "message": "Payment intent creado exitosamente",
  "data": {
    "client_secret": "pi_xxx_secret_xxx",
    "payment_intent_id": "pi_xxx",
    "amount": 1000,
    "currency": "usd"
  }
}
```

### 2. Confirm Payment
**Endpoint:** `/admin/backws/stripe/confirm-payment.php`

**Method:** POST

**Parameters:**
- `payment_intent_client_secret` (required): Client secret from payment intent
- `payment_method` (required): Payment method details
  - `type`: "card"
  - `card`: Card details (number, exp_month, exp_year, cvc)
  - `billing_details`: Billing information
- `compania` (required): Company ID
- `usuario_id` (required): User ID

**Response:**
```json
{
  "code": 0,
  "message": "Pago procesado exitosamente",
  "data": {
    "payment_intent_id": "pi_xxx",
    "status": "succeeded",
    "amount": 1000,
    "currency": "usd"
  }
}
```

### 3. Webhook Handler
**Endpoint:** `/admin/backws/stripe/webhook.php`

Configure this endpoint in your Stripe Dashboard to receive webhook events.

**Supported Events:**
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `charge.succeeded`
- `charge.failed`
- `transfer.created`
- `payout.created`
- `payout.paid`
- `payout.failed`
- `account.updated`

## Stripe Connect Flow

1. **Customer Payment**: Customer initiates payment through the app
2. **Payment Intent**: Backend creates a payment intent with destination charges
3. **Card Details**: Customer enters card details (should use Stripe Elements in production)
4. **Confirmation**: Payment is confirmed and processed
5. **Split Payment**: 
   - Platform fee is deducted (configurable, default 10%)
   - Remaining amount is transferred to destination account
6. **Balance Update**: User balances are updated in the database
7. **Notifications**: Both sender and receiver get notified

## Security Considerations

⚠️ **IMPORTANT**: 
- Never send raw card data to your backend in production
- Use Stripe Elements or Payment Request API for PCI compliance
- Store Stripe keys securely and never commit them to version control
- Always verify webhook signatures
- Use HTTPS in production

## Testing

### Test Cards

Use these test card numbers for development:

- **Success**: 4242 4242 4242 4242
- **Decline**: 4000 0000 0000 0002
- **3D Secure**: 4000 0025 0000 3155

### Test Connect Accounts

To test Stripe Connect:

1. Create a Connect account in Stripe Dashboard (Test mode)
2. Update user's `stripe_account_id` in the database
3. Enable `stripe_connect_enabled` for the company

## Troubleshooting

### Common Issues

1. **Payment Intent Creation Failed**
   - Check Stripe API keys are correct
   - Verify company configuration in database
   - Check error logs for specific error messages

2. **Payment Confirmation Failed**
   - Ensure payment method details are correct
   - Check if customer needs 3D Secure authentication
   - Verify payment intent status

3. **Webhook Not Working**
   - Verify webhook endpoint is accessible
   - Check webhook secret is correct
   - Review webhook logs in Stripe Dashboard

## Support

For Stripe-specific issues, refer to:
- [Stripe Documentation](https://stripe.com/docs)
- [Stripe Connect Guide](https://stripe.com/docs/connect)
- [Stripe PHP SDK](https://github.com/stripe/stripe-php)