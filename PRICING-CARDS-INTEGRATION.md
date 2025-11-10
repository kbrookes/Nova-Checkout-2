# 🎨 Pricing Cards Integration Guide

This guide shows how to integrate the Nova Checkout shortcode with your existing Bricks pricing cards and billing period toggle.

---

## 🎯 How It Works

Your pricing cards already have:
- ✅ Billing period toggle (Quarterly/Yearly)
- ✅ If:so conditional pricing display
- ✅ JavaScript that adds URL parameters to "Buy Now" buttons
- ✅ Geolocation-based routing (AU/NZ)

The Nova Checkout shortcode now:
- ✅ Reads URL parameters (`?tier=professional&period=annual`)
- ✅ Pre-selects the tier and billing period
- ✅ Shows your custom toggle switch design
- ✅ Only requires user to enter number of users

---

## 📋 Setup Instructions

### 1. Create Checkout Pages

Create two pages in WordPress:

**Australian Checkout Page** (`/checkout/`)
```
[nova_checkout country="au" success_url="/thank-you/"]
```

**New Zealand Checkout Page** (`/checkout-nz/`)
```
[nova_checkout country="nz" success_url="/thank-you/"]
```

### 2. Update Your Pricing Card Buttons

Your existing JavaScript already handles this! It adds the correct parameters:
- `?tier=standard&period=quarterly`
- `?tier=professional&period=annual`
- etc.

The shortcode will automatically:
1. Read the `tier` parameter → hide tier selection, pre-select the tier
2. Read the `period` parameter → pre-select quarterly or annual
3. Show the toggle switch with the correct option selected
4. User only needs to enter number of users and click checkout

---

## 🎨 Shortcode Parameters

### Basic Usage (Recommended)

```
[nova_checkout country="au"]
```

This will:
- ✅ Read `tier` and `period` from URL parameters
- ✅ Show the toggle switch for billing period
- ✅ Pre-select everything based on URL params
- ✅ Hide tier selection if tier is in URL

### Advanced Options

```
[nova_checkout 
    country="au"
    success_url="/thank-you/"
    cancel_url="/pricing/"
    button_text="Complete Checkout"
    use_url_params="true"
    show_period_toggle="true"
    default_users="10"
]
```

#### All Parameters:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `country` | `au` | Country code: `au` or `nz` |
| `success_url` | `/checkout-success/` | Redirect after successful payment |
| `cancel_url` | Current page | Redirect if user cancels |
| `button_text` | `Continue to Checkout` | Submit button text |
| `use_url_params` | `true` | Read tier/period from URL |
| `show_period_toggle` | `true` | Show toggle switch (vs dropdown) |
| `show_tiers` | `true` | Show tier selection (auto-hidden if tier in URL) |
| `default_tier` | _(empty)_ | Fallback tier if not in URL |
| `default_billing_period` | _(empty)_ | Fallback period if not in URL |
| `default_users` | `5` | Default number of users |

---

## 🔄 User Flow

### Current Flow (With Your Pricing Cards)

1. **User visits pricing page** (`/pricing/`)
   - Sees pricing cards with if:so conditional pricing
   - Toggles between Quarterly/Yearly
   - Clicks "Buy Now" on a card (e.g., Professional)

2. **JavaScript updates the button URL**
   - Adds parameters: `/checkout/?tier=professional&period=annual`
   - Routes to correct country page based on geolocation

3. **User lands on checkout page**
   - Shortcode reads URL parameters
   - Pre-selects Professional tier (hides tier selection)
   - Pre-selects Annual billing (toggle shows "Yearly" selected)
   - User enters number of users (e.g., 10)
   - Clicks "Continue to Checkout"

4. **Redirects to Stripe Checkout**
   - Creates session for Professional Annual with 10 users
   - User completes payment on Stripe
   - Returns to success page

---

## 🎨 Toggle Switch Styling

The shortcode includes your toggle switch design by default when `show_period_toggle="true"`.

### HTML Structure (Rendered by Shortcode)

```html
<div class="switch-wrapper">
    <input id="nova-quarterly" type="radio" name="billing_period" value="quarterly" checked>
    <input id="nova-yearly" type="radio" name="billing_period" value="annual">
    <label for="nova-quarterly">Quarterly</label>
    <label for="nova-yearly">Yearly</label>
    <span class="highlighter"></span>
</div>
```

### Customize Toggle Colors

Add this CSS to match your brand:

```css
/* Change toggle background */
.switch-wrapper {
    background: #f0f0f0;
}

/* Change active toggle color */
.switch-wrapper .highlighter {
    background: #your-brand-color;
}

/* Change label colors */
.switch-wrapper input[type="radio"]:checked + label {
    color: #fff;
}
```

---

## 🧪 Testing the Integration

### Test Flow 1: From Pricing Cards

1. Go to your pricing page
2. Toggle between Quarterly/Yearly
3. Click "Buy Now" on any tier
4. Verify checkout page shows:
   - ✅ Correct tier pre-selected (tier selection hidden)
   - ✅ Correct billing period pre-selected on toggle
   - ✅ User count field ready for input
5. Enter number of users
6. Click checkout
7. Verify Stripe session has correct tier, period, and user count

### Test Flow 2: Direct URL Access

Test these URLs directly:

**Standard Quarterly:**
```
/checkout/?tier=standard&period=quarterly
```

**Professional Annual:**
```
/checkout/?tier=professional&period=annual
```

**Ultimate Quarterly:**
```
/checkout/?tier=ultimate&period=quarterly
```

Each should pre-select the correct tier and period.

### Test Flow 3: No URL Parameters

Visit `/checkout/` with no parameters:
- Should show tier selection
- Should show toggle with Quarterly selected by default
- User can choose everything manually

---

## 🎯 Example Scenarios

### Scenario 1: User Clicks "Professional" Card with "Yearly" Toggle

**URL:** `/checkout/?tier=professional&period=annual`

**Checkout Page Shows:**
- Tier: Professional (hidden, pre-selected)
- Billing: Toggle with "Yearly" selected
- Users: Input field (default: 5)
- Button: "Continue to Checkout"

### Scenario 2: User Clicks "Standard" Card with "Quarterly" Toggle

**URL:** `/checkout/?tier=standard&period=quarterly`

**Checkout Page Shows:**
- Tier: Standard (hidden, pre-selected)
- Billing: Toggle with "Quarterly" selected
- Users: Input field (default: 5)
- Button: "Continue to Checkout"

### Scenario 3: Direct Access (No Parameters)

**URL:** `/checkout/`

**Checkout Page Shows:**
- Tier: Radio buttons for Standard/Professional/Ultimate
- Billing: Toggle with "Quarterly" selected
- Users: Input field (default: 5)
- Button: "Continue to Checkout"

---

## 🔧 Advanced Customization

### Option 1: Use Dropdown Instead of Toggle

If you want to use a dropdown for billing period on the checkout page:

```
[nova_checkout show_period_toggle="false"]
```

### Option 2: Always Show Tier Selection

If you want to always show tier selection even when tier is in URL:

```
[nova_checkout show_tiers="true" use_url_params="false"]
```

### Option 3: Set Default Values Without URL Params

```
[nova_checkout 
    default_tier="professional" 
    default_billing_period="annual"
    default_users="10"
    use_url_params="false"
]
```

### Option 4: Combine URL Params with Fallbacks

```
[nova_checkout 
    use_url_params="true"
    default_tier="standard"
    default_billing_period="quarterly"
]
```

This will:
- Use URL params if present
- Fall back to defaults if URL params are missing

---

## 🎨 Matching Your Brand

### Custom Button Styling

```css
.nova-submit-button {
    background: #your-brand-color !important;
    font-family: your-font-family;
}

.nova-submit-button:hover {
    background: #your-hover-color !important;
}
```

### Custom Form Styling

```css
.nova-checkout-form {
    background: #your-bg-color;
    border: 2px solid #your-border-color;
}

.nova-label {
    color: #your-text-color;
    font-family: your-font-family;
}
```

### Match Toggle to Pricing Page

If your pricing page toggle has different colors, update:

```css
.nova-checkout-form .switch-wrapper .highlighter {
    background: #same-as-pricing-page;
}
```

---

## 🐛 Troubleshooting

### Toggle not showing correct selection

**Check:** URL parameter format
- ✅ Correct: `?period=quarterly` or `?period=annual`
- ❌ Wrong: `?period=yearly` (should be `annual`)

Your JavaScript uses `annual` for yearly, which matches the shortcode.

### Tier not pre-selected

**Check:** URL parameter format
- ✅ Correct: `?tier=standard`, `?tier=professional`, `?tier=ultimate`
- ❌ Wrong: `?tier=Standard` (case matters)

### Wrong country routing

**Check:** Shortcode country parameter
- AU page: `[nova_checkout country="au"]`
- NZ page: `[nova_checkout country="nz"]`

### Toggle switch styling broken

**Check:** CSS conflicts
- Your theme might override `.switch-wrapper` styles
- Add `!important` to critical styles if needed

---

## 📞 Support

For issues:
1. Check browser console for JavaScript errors
2. Verify URL parameters are correct
3. Check WordPress error logs
4. Enable debug mode: `define('WP_DEBUG', true);`

---

## ✅ Checklist

- [ ] Created `/checkout/` page with `[nova_checkout country="au"]`
- [ ] Created `/checkout-nz/` page with `[nova_checkout country="nz"]`
- [ ] Tested clicking "Buy Now" from pricing cards
- [ ] Verified tier and period are pre-selected correctly
- [ ] Tested toggle switch functionality
- [ ] Tested complete checkout flow to Stripe
- [ ] Verified correct price IDs are used for each tier/period
- [ ] Created success page at `/thank-you/`
- [ ] Tested with both AU and NZ routing

