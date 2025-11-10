# 📋 Form Integration Guide

This guide shows you how to integrate Nova Stripe Checkout v2 with your forms.

---

## 🚀 Quick Start: Using the Shortcode (Easiest)

The simplest way to add a checkout form is using the `[nova_checkout]` shortcode.

### Basic Usage

Add this to any WordPress page or post:

```
[nova_checkout]
```

This will display a complete checkout form with:
- Tier selection (Standard, Professional, Ultimate)
- Billing period dropdown (Quarterly, Annual)
- Number of users input
- Submit button

### Shortcode Parameters

Customize the form with these optional parameters:

```
[nova_checkout 
    country="au" 
    success_url="https://yoursite.com/thank-you/" 
    cancel_url="https://yoursite.com/pricing/" 
    button_text="Subscribe Now"
    show_tiers="true"
    default_tier="professional"
]
```

#### Available Parameters:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `country` | `au` | Country code: `au` or `nz` |
| `success_url` | `/checkout-success/` | URL to redirect after successful payment |
| `cancel_url` | Current page | URL to redirect if user cancels |
| `button_text` | `Continue to Checkout` | Text for the submit button |
| `show_tiers` | `true` | Show tier selection (`true` or `false`) |
| `default_tier` | _(empty)_ | Pre-select a tier: `standard`, `professional`, or `ultimate` |

### Examples

**1. Simple Australian checkout:**
```
[nova_checkout]
```

**2. New Zealand checkout with custom URLs:**
```
[nova_checkout country="nz" success_url="/success/" cancel_url="/pricing/"]
```

**3. Fixed tier (Professional only):**
```
[nova_checkout show_tiers="false" default_tier="professional"]
```

**4. Custom button text:**
```
[nova_checkout button_text="Get Started Now →"]
```

---

## 🔧 Advanced: Custom JavaScript Integration

For more control, you can create a custom form and call the API directly.

### HTML Form

```html
<form id="my-checkout-form">
    <select name="tier" required>
        <option value="">Select Plan...</option>
        <option value="standard">Standard</option>
        <option value="professional">Professional</option>
        <option value="ultimate">Ultimate</option>
    </select>
    
    <select name="billing_period" required>
        <option value="">Select Billing...</option>
        <option value="quarterly">Quarterly</option>
        <option value="annual">Annual</option>
    </select>
    
    <input type="number" name="users" min="1" value="5" required>
    
    <button type="submit">Checkout</button>
</form>
```

### JavaScript Handler

```javascript
document.getElementById('my-checkout-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const button = e.target.querySelector('button');
    
    button.disabled = true;
    button.textContent = 'Processing...';
    
    try {
        const response = await fetch('/wp-json/nova/v1/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                country: 'au',  // or 'nz'
                tier: formData.get('tier'),
                billing_period: formData.get('billing_period'),
                users: parseInt(formData.get('users')),
                success_url: window.location.origin + '/success/',
                cancel_url: window.location.href
            })
        });
        
        const data = await response.json();
        
        if (data.url) {
            // Redirect to Stripe Checkout
            window.location.href = data.url;
        } else {
            alert('Error: ' + (data.message || 'Failed to create checkout'));
            button.disabled = false;
            button.textContent = 'Checkout';
        }
    } catch (error) {
        alert('Error: ' + error.message);
        button.disabled = false;
        button.textContent = 'Checkout';
    }
});
```

---

## 📡 API Reference

### Endpoint

```
POST /wp-json/nova/v1/checkout
```

### Request Body

```json
{
    "country": "au",
    "tier": "professional",
    "billing_period": "annual",
    "users": 10,
    "success_url": "https://yoursite.com/success/",
    "cancel_url": "https://yoursite.com/pricing/"
}
```

### Parameters

| Field | Type | Required | Values | Description |
|-------|------|----------|--------|-------------|
| `country` | string | Yes | `au`, `nz` | Country for Stripe account routing |
| `tier` | string | Yes | `standard`, `professional`, `ultimate` | Pricing tier |
| `billing_period` | string | Yes | `quarterly`, `annual` | Billing frequency |
| `users` | integer | Yes | 1-1000 | Number of user seats |
| `success_url` | string | Yes | Valid URL | Redirect after successful payment |
| `cancel_url` | string | Yes | Valid URL | Redirect if user cancels |

### Response (Success)

```json
{
    "success": true,
    "url": "https://checkout.stripe.com/c/pay/cs_test_...",
    "session_id": "cs_test_..."
}
```

### Response (Error)

```json
{
    "code": "invalid_parameter",
    "message": "Invalid tier specified",
    "data": {
        "status": 400
    }
}
```

---

## 🎨 Styling the Shortcode Form

The shortcode form uses CSS classes prefixed with `nova-` for easy customization.

### CSS Classes

```css
.nova-checkout-form-wrapper { }  /* Outer container */
.nova-checkout-form { }          /* Form element */
.nova-form-group { }             /* Form field wrapper */
.nova-label { }                  /* Field labels */
.nova-tier-option { }            /* Tier selection boxes */
.nova-tier-option.selected { }   /* Selected tier */
.nova-tier-badge { }             /* Tier badges */
.nova-tier-standard { }          /* Standard tier badge */
.nova-tier-professional { }      /* Professional tier badge */
.nova-tier-ultimate { }          /* Ultimate tier badge */
.nova-select { }                 /* Dropdown fields */
.nova-input { }                  /* Text/number inputs */
.nova-submit-button { }          /* Submit button */
.nova-error { }                  /* Error message box */
```

### Example: Custom Styling

Add this to your theme's CSS:

```css
/* Change button color */
.nova-submit-button {
    background: #FF6B6B !important;
}

.nova-submit-button:hover {
    background: #EE5A5A !important;
}

/* Customize tier badges */
.nova-tier-ultimate {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

/* Make form wider */
.nova-checkout-form-wrapper {
    max-width: 800px !important;
}
```

---

## 🔐 Security Notes

1. **API Keys**: Never expose your Stripe secret keys in frontend code
2. **Validation**: All parameters are validated server-side
3. **HTTPS**: Always use HTTPS in production
4. **Webhooks**: Configure webhook signature verification in Settings → Nova Settings

---

## 🧪 Testing

### Test Mode

1. Use Stripe test API keys in Settings → Nova Settings
2. Use test price IDs in Settings → Nova Prices
3. Test with Stripe test card: `4242 4242 4242 4242`

### Success/Cancel URLs

Create these pages on your site:
- `/checkout-success/` - Thank you page after successful payment
- `/checkout-cancel/` - Page shown when user cancels (optional)

---

## 💡 Common Use Cases

### 1. Pricing Page with Multiple Tiers

```
<div class="pricing-grid">
    <div class="pricing-card">
        <h3>Standard</h3>
        <p>$99/month</p>
        [nova_checkout show_tiers="false" default_tier="standard" button_text="Choose Standard"]
    </div>
    
    <div class="pricing-card">
        <h3>Professional</h3>
        <p>$199/month</p>
        [nova_checkout show_tiers="false" default_tier="professional" button_text="Choose Professional"]
    </div>
    
    <div class="pricing-card">
        <h3>Ultimate</h3>
        <p>$399/month</p>
        [nova_checkout show_tiers="false" default_tier="ultimate" button_text="Choose Ultimate"]
    </div>
</div>
```

### 2. Simple "Get Started" Button

```
[nova_checkout show_tiers="false" default_tier="professional" button_text="Get Started Free"]
```

### 3. Regional Forms

**Australian Page:**
```
[nova_checkout country="au" success_url="/au-success/"]
```

**New Zealand Page:**
```
[nova_checkout country="nz" success_url="/nz-success/"]
```

---

## 🐛 Troubleshooting

### Form doesn't submit

1. Check browser console for JavaScript errors
2. Verify API keys are configured in Settings → Nova Settings
3. Verify price IDs are configured in Settings → Nova Prices
4. Check that the country/tier/period combination has a price ID

### Redirects to wrong Stripe account

- Verify the `country` parameter matches your intended Stripe account
- Check that you've configured API keys for both AU and NZ if using both

### "Invalid price ID" error

- Go to Settings → Nova Prices
- Verify the price ID exists for the selected country/tier/period combination
- Check that the price ID is from the correct Stripe account (AU or NZ)

---

## 📞 Support

For issues or questions:
1. Check the main [README.md](README.md) for general documentation
2. Review Stripe Dashboard for payment/subscription details
3. Check WordPress error logs for server-side issues
4. Enable WordPress debug mode: `define('WP_DEBUG', true);`

