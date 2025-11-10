# Nova Stripe Checkout v2

WordPress plugin for Stripe Checkout subscriptions with per-seat pricing, three pricing tiers, and AU/NZ account routing.

## Features

- ✅ **Stripe Checkout** integration for subscription payments
- ✅ **Per-seat pricing** with configurable quantity
- ✅ **Three pricing tiers** - Standard, Professional, and Ultimate
- ✅ **Multi-country support** - Route to Australian or New Zealand Stripe accounts
- ✅ **Flexible billing** - Quarterly or annual subscription periods
- ✅ **Easy price management** - Simple admin interface for price ID configuration
- ✅ **Webhook handling** - Automatic processing of Stripe events
- ✅ **Secure** - Input validation, webhook signature verification, no secret logging
- ✅ **WordPress Coding Standards** compliant
- ✅ **PHPStan Level 7** static analysis
- ✅ **Git Updater** compatible

## Requirements

- WordPress 6.0 or higher
- PHP 8.1 or higher
- Stripe account(s) for AU and/or NZ
- HTTPS enabled (required for Stripe)

## Installation

### Via Git Updater (Recommended)

1. Install [Git Updater](https://git-updater.com/) plugin
2. Add this repository URL to Git Updater
3. Install and activate the plugin

### Manual Installation

1. Download the latest release
2. Upload to `/wp-content/plugins/nova-checkout/`
3. Run `composer install --no-dev` in the plugin directory
4. Activate the plugin through WordPress admin

## Configuration

### 1. Stripe API Keys

Navigate to **Settings → Nova Checkout** and enter your Stripe API keys:

- **Australia Secret Key** - Your Stripe secret key for AU account
- **New Zealand Secret Key** - Your Stripe secret key for NZ account
- **Australia Webhook Secret** - Webhook signing secret for AU
- **New Zealand Webhook Secret** - Webhook signing secret for NZ

**Alternative:** Set via environment variables:
```bash
AU_STRIPE_SECRET_KEY=sk_live_...
NZ_STRIPE_SECRET_KEY=sk_live_...
AU_STRIPE_WEBHOOK_SECRET=whsec_...
NZ_STRIPE_WEBHOOK_SECRET=whsec_...
```

### 2. Price IDs

Navigate to **Settings → Nova Prices** and configure your Stripe price IDs for each tier:

**Australia:**
- **Standard - Quarterly** - Price ID for AU standard quarterly billing
- **Standard - Annual** - Price ID for AU standard annual billing
- **Professional - Quarterly** - Price ID for AU professional quarterly billing
- **Professional - Annual** - Price ID for AU professional annual billing
- **Ultimate - Quarterly** - Price ID for AU ultimate quarterly billing
- **Ultimate - Annual** - Price ID for AU ultimate annual billing

**New Zealand:**
- **Standard - Quarterly** - Price ID for NZ standard quarterly billing
- **Standard - Annual** - Price ID for NZ standard annual billing
- **Professional - Quarterly** - Price ID for NZ professional quarterly billing
- **Professional - Annual** - Price ID for NZ professional annual billing
- **Ultimate - Quarterly** - Price ID for NZ ultimate quarterly billing
- **Ultimate - Annual** - Price ID for NZ ultimate annual billing

**Note:** Create these prices in your Stripe dashboard first. You'll need 12 price IDs total (3 tiers × 2 periods × 2 countries).

### 3. Webhooks

Configure webhooks in your Stripe dashboard:

**Australia Account:**
- URL: `https://yoursite.com/wp-json/nova/v1/stripe-webhook/au`
- Events: `checkout.session.completed`, `customer.subscription.*`, `invoice.payment_*`

**New Zealand Account:**
- URL: `https://yoursite.com/wp-json/nova/v1/stripe-webhook/nz`
- Events: `checkout.session.completed`, `customer.subscription.*`, `invoice.payment_*`

## Usage

### Creating a Checkout Session

Make a POST request to `/wp-json/nova/v1/checkout`:

```javascript
fetch('/wp-json/nova/v1/checkout', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    country: 'au',                    // 'au' or 'nz'
    tier: 'professional',             // 'standard', 'professional', or 'ultimate'
    billing_period: 'quarterly',      // 'quarterly' or 'annual'
    users: 5,                         // Number of seats
    success_url: 'https://example.com/success',
    cancel_url: 'https://example.com/cancel',
    customer_email: 'customer@example.com',  // Optional
    metadata: {                       // Optional
      source: 'website',
      campaign: 'spring2024'
    }
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    // Redirect to Stripe Checkout
    window.location.href = data.session_url;
  }
});
```

### API Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `country` | string | Yes | Country code: `au` or `nz` |
| `tier` | string | Yes | Pricing tier: `standard`, `professional`, or `ultimate` |
| `billing_period` | string | Yes | Billing period: `quarterly` or `annual` |
| `users` | integer | Yes | Number of seats/users (minimum: 1) |
| `success_url` | string | Yes | URL to redirect after successful checkout |
| `cancel_url` | string | Yes | URL to redirect if checkout is cancelled |
| `customer_email` | string | No | Pre-fill customer email address |
| `metadata` | object | No | Custom metadata to attach to the session |

### Response

**Success:**
```json
{
  "success": true,
  "session_id": "cs_test_...",
  "session_url": "https://checkout.stripe.com/c/pay/cs_test_..."
}
```

**Error:**
```json
{
  "code": "nova_checkout_error",
  "message": "Error message",
  "data": {
    "status": 400
  }
}
```

### Example: Bricks Form Integration

Add this JavaScript to your Bricks form:

```javascript
document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('#your-form-id');
  
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(form);
    
    const response = await fetch('/wp-json/nova/v1/checkout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        country: formData.get('country'),
        tier: formData.get('tier'),
        billing_period: formData.get('billing_period'),
        users: parseInt(formData.get('users')),
        success_url: window.location.origin + '/success',
        cancel_url: window.location.origin + '/cancel',
        customer_email: formData.get('email'),
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      window.location.href = data.session_url;
    } else {
      alert('Error: ' + data.message);
    }
  });
});
```

## Webhook Events

The plugin fires WordPress actions for custom handling:

```php
// Generic webhook event
add_action('nova_checkout_webhook_event', function($event, $country) {
  // Handle any webhook event
}, 10, 2);

// Specific events
add_action('nova_checkout_session_completed', function($session, $country) {
  // Checkout completed
}, 10, 2);

add_action('nova_checkout_subscription_created', function($subscription, $country) {
  // New subscription
}, 10, 2);

add_action('nova_checkout_subscription_updated', function($subscription, $country) {
  // Subscription updated
}, 10, 2);

add_action('nova_checkout_subscription_deleted', function($subscription, $country) {
  // Subscription cancelled
}, 10, 2);

add_action('nova_checkout_invoice_payment_succeeded', function($invoice, $country) {
  // Payment successful
}, 10, 2);

add_action('nova_checkout_invoice_payment_failed', function($invoice, $country) {
  // Payment failed
}, 10, 2);
```

## Development

### Setup

```bash
# Install dependencies
composer install

# Run all checks
composer test

# Individual checks
composer lint      # PHP syntax check
composer phpcs     # Coding standards
composer phpstan   # Static analysis
composer phpcpd    # Copy/paste detection
composer audit     # Security audit
```

### Code Standards

- PHP 8.1+ with strict types
- WordPress Coding Standards
- PHPStan Level 7
- Comprehensive docblocks
- Early returns, explicit types
- No secrets in logs or errors

## Security

- All inputs are sanitized and validated
- Webhook signatures are verified
- API keys never logged or exposed
- User-friendly error messages (no sensitive data)
- HTTPS required for production

## Support

For issues, questions, or contributions, please use the GitHub repository.

## License

GPL v2 or later

## Credits

Built with:
- [Stripe PHP SDK](https://github.com/stripe/stripe-php)
- WordPress REST API
- Love and coffee ☕

