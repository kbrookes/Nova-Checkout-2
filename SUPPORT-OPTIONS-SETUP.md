# Support Options Setup Guide

## Overview

This guide covers the implementation of tiered support options for Act! Advantage subscriptions. Support is offered as a **separate subscription** that appears as a distinct line item on customer invoices.

---

## Support Tiers

### 1. Self-service (Included - Free)
**Default option, no additional charge**

- Self-service portal (Knowledge Base, FAQs, guides & videos)
- Level 1 Act! error troubleshooting live chat, email or support ticket

### 2. Support+Phone
**Per-user pricing, varies by product tier**

Everything in Self-service, plus:
- Priority queuing
- Feature guidance & troubleshooting by phone
- 30-minute onboarding call

**Pricing:**
- **Standard**: $18 AUD/user/month (available up to 2 users only)
- **Professional**: $9 AUD/user/month (available up to 5 users only)
- **Ultimate**: Not available

### 3. Support+Trainer
**Flat monthly fee: AU$49 / NZ$55**

Everything above plus:
- 2 hours/year screen-share Level 2* support/setup/training (total 30 min per 3 months)
- *Direct access to an expert with over 15 years' experience

### 4. Support+Coach
**Flat monthly fee: AU$74 / NZ$83**

Everything above plus:
- 2 extra hours/year screen-share Level 2 coaching/customisation (total 60 min per 3 months)
- Discount on extra hours (Save $20 per hour)

### 5. Support+Specialist
**Flat monthly fee: AU$99 / NZ$110**

Everything above plus:
- 2 extra hours/year screen-share Level 3* consulting/development (total 90 min per 3 months)
- *Access to specialists for programming, scripting & advanced project work

---

## Dynamic Filtering Logic

Support options are filtered based on product tier and user count to prevent customers from paying more than necessary.

### Rules:

1. **Support+Phone on Standard**: Hide when users > 2
   - At 3 users: $54/month > Support+Trainer $49/month

2. **Support+Phone on Professional**: Hide when users > 5
   - At 6 users: $54/month > Support+Trainer $49/month

3. **Support+Phone on Ultimate**: Always hidden (not available)

4. **Flat-rate tiers** (Trainer/Coach/Specialist): Always available for all product tiers

---

## Stripe Configuration

### Recommended Approach: Separate Products

Create **2 Stripe Products**:

1. **Act! Advantage Subscription** (existing)
   - Standard, Professional, Ultimate tiers
   - Per-seat pricing

2. **Act! Advantage Support** (new)
   - Self-service, Phone, Trainer, Coach, Specialist tiers
   - Mixed pricing (per-seat and flat-rate)

### Benefits:
✅ Separate line items on invoice  
✅ Easier to manage and report on  
✅ Clearer for customers  
✅ Flexible for future changes  

---

## Price IDs Required

### Total: 40 Price IDs

**Product Prices: 12** (already configured)
- 3 tiers × 2 periods × 2 countries

**Support Prices: 28** (new)

#### Per-User Support (8 price IDs):
- Support+Phone Standard: AU Quarterly, AU Annual, NZ Quarterly, NZ Annual
- Support+Phone Professional: AU Quarterly, AU Annual, NZ Quarterly, NZ Annual

#### Flat-Rate Support (20 price IDs):
- Support+Trainer: AU Quarterly, AU Annual, NZ Quarterly, NZ Annual
- Support+Coach: AU Quarterly, AU Annual, NZ Quarterly, NZ Annual
- Support+Specialist: AU Quarterly, AU Annual, NZ Quarterly, NZ Annual
- Self-service (Free): AU Quarterly, AU Annual, NZ Quarterly, NZ Annual

**Note:** Self-service $0 price IDs are optional. Can be handled by not adding a support subscription.

---

## Price Calculations

### Support+Phone (Per-User)

**Standard Tier:**
- AU: $18/user/month
  - Quarterly: $54/user (3 months)
  - Annual: $216/user (12 months)
- NZ: $20/user/month ($18 × 1.12 = $20.16 → $20)
  - Quarterly: $60/user
  - Annual: $240/user

**Professional Tier:**
- AU: $9/user/month
  - Quarterly: $27/user
  - Annual: $108/user
- NZ: $10/user/month ($9 × 1.12 = $10.08 → $10)
  - Quarterly: $30/user
  - Annual: $120/user

### Flat-Rate Support

**Support+Trainer:**
- AU: $49/month → Quarterly: $147, Annual: $588
- NZ: $55/month → Quarterly: $165, Annual: $660

**Support+Coach:**
- AU: $74/month → Quarterly: $222, Annual: $888
- NZ: $83/month → Quarterly: $249, Annual: $996

**Support+Specialist:**
- AU: $99/month → Quarterly: $297, Annual: $1,188
- NZ: $110/month → Quarterly: $330, Annual: $1,320

**Self-service:**
- Free: $0 (all periods and countries)

---

## Creating Stripe Price IDs

### Step 1: Create Support Product

1. Go to Stripe Dashboard → Products
2. Click "Add product"
3. Name: **Act! Advantage Support**
4. Description: **Support add-on for Act! Advantage subscriptions**
5. Save product

### Step 2: Create Price IDs for Each Support Tier

For each support tier, create prices for both billing periods and both countries.

#### Support+Phone Standard (Per-User)

**Australia:**
- Quarterly: $54 AUD per user, recurring every 3 months
- Annual: $216 AUD per user, recurring every 12 months

**New Zealand:**
- Quarterly: $60 NZD per user, recurring every 3 months
- Annual: $240 NZD per user, recurring every 12 months

#### Support+Phone Professional (Per-User)

**Australia:**
- Quarterly: $27 AUD per user, recurring every 3 months
- Annual: $108 AUD per user, recurring every 12 months

**New Zealand:**
- Quarterly: $30 NZD per user, recurring every 3 months
- Annual: $120 NZD per user, recurring every 12 months

#### Support+Trainer (Flat-Rate)

**Australia:**
- Quarterly: $147 AUD, recurring every 3 months
- Annual: $588 AUD, recurring every 12 months

**New Zealand:**
- Quarterly: $165 NZD, recurring every 3 months
- Annual: $660 NZD, recurring every 12 months

#### Support+Coach (Flat-Rate)

**Australia:**
- Quarterly: $222 AUD, recurring every 3 months
- Annual: $888 AUD, recurring every 12 months

**New Zealand:**
- Quarterly: $249 NZD, recurring every 3 months
- Annual: $996 NZD, recurring every 12 months

#### Support+Specialist (Flat-Rate)

**Australia:**
- Quarterly: $297 AUD, recurring every 3 months
- Annual: $1,188 AUD, recurring every 12 months

**New Zealand:**
- Quarterly: $330 NZD, recurring every 3 months
- Annual: $1,320 NZD, recurring every 12 months

#### Self-service (Optional - Free)

**All Countries/Periods:**
- $0 (can skip creating these and handle as "no support subscription")

### Step 3: Copy Price IDs

After creating each price, copy the Price ID (starts with `price_`) and save it for plugin configuration.

---

## Plugin Configuration

### Data Structure

Support prices are stored separately from product prices:

```php
$support_prices = [
    'au' => [
        'phone_standard' => [
            'quarterly' => 'price_xxx',
            'annual' => 'price_xxx'
        ],
        'phone_professional' => [
            'quarterly' => 'price_xxx',
            'annual' => 'price_xxx'
        ],
        'trainer' => [
            'quarterly' => 'price_xxx',
            'annual' => 'price_xxx'
        ],
        'coach' => [
            'quarterly' => 'price_xxx',
            'annual' => 'price_xxx'
        ],
        'specialist' => [
            'quarterly' => 'price_xxx',
            'annual' => 'price_xxx'
        ]
    ],
    'nz' => [
        // Same structure as 'au'
    ]
];
```

### Admin Interface

The plugin admin will have a new "Support Prices" tab with a table layout:

| Support Tier | Quarterly | Annual |
|--------------|-----------|--------|
| Phone (Standard) | price_xxx | price_xxx |
| Phone (Professional) | price_xxx | price_xxx |
| Trainer | price_xxx | price_xxx |
| Coach | price_xxx | price_xxx |
| Specialist | price_xxx | price_xxx |

Separate tabs for Australia and New Zealand.

---

## Checkout Flow

### User Experience

1. **Select Billing Period** (Quarterly/Annual toggle)
2. **Select Product Tier** (Standard/Professional/Ultimate)
3. **Select Support Level** (Self-service/Phone/Trainer/Coach/Specialist)
   - Options dynamically filtered based on product tier and user count
   - Each option shows description of included features
4. **Enter Number of Users**
   - Support options update in real-time as user count changes
5. **Continue to Checkout** → Stripe Checkout with 2 line items

### Stripe Checkout Session

When creating the checkout session, include 2 line items:

```php
'line_items' => [
    [
        'price' => $product_price_id,
        'quantity' => $users
    ],
    [
        'price' => $support_price_id,
        'quantity' => $support_is_per_user ? $users : 1
    ]
]
```

**Note:** If support level is "Self-service" (free), only include the product line item.

---

## Testing Scenarios

### Scenario 1: Standard + 2 Users + Support+Phone
- Product: Standard Quarterly × 2 users
- Support: Phone Standard Quarterly × 2 users
- Both line items appear on invoice

### Scenario 2: Standard + 3 Users (Phone Hidden)
- Product: Standard Quarterly × 3 users
- Support+Phone option should be hidden (would cost $54/month vs Trainer $49/month)
- Only Trainer/Coach/Specialist available

### Scenario 3: Professional + 5 Users + Support+Phone
- Product: Professional Quarterly × 5 users
- Support: Phone Professional Quarterly × 5 users
- Both line items appear on invoice

### Scenario 4: Professional + 6 Users (Phone Hidden)
- Product: Professional Quarterly × 6 users
- Support+Phone option should be hidden (would cost $54/month vs Trainer $49/month)
- Only Trainer/Coach/Specialist available

### Scenario 5: Ultimate + Any Users + Trainer
- Product: Ultimate Annual × 10 users
- Support: Trainer Annual × 1 (flat rate)
- Support+Phone never available for Ultimate tier

### Scenario 6: Any Tier + Self-service
- Product: Any tier × any users
- Support: None (self-service is free/included)
- Only product line item on invoice

---

## Implementation Checklist

- [ ] Create "Act! Advantage Support" product in Stripe
- [ ] Create 28 support price IDs in Stripe (or 20 if skipping $0 prices)
- [ ] Update `includes/Prices.php` to handle support prices
- [ ] Update admin interface with "Support Prices" section
- [ ] Update `includes/REST.php` to accept `support_level` parameter
- [ ] Update `includes/REST.php` to create checkout sessions with 2 line items
- [ ] Update `includes/Shortcode.php` with support selector UI
- [ ] Add JavaScript for dynamic support filtering
- [ ] Add helper functions for support price lookups
- [ ] Test all scenarios listed above
- [ ] Update main README.md with support options documentation

---

## Support Changes & Upgrades

Customers **cannot** change their support level themselves through the checkout. They must contact the provider to:

- Upgrade support tier
- Downgrade support tier
- Cancel support subscription

This is handled manually through Stripe Dashboard or customer support processes.

---

## Questions?

If you encounter any issues during setup, check:

1. **Price IDs are correct** in plugin settings
2. **Currency matches country** (AUD for Australia, NZD for New Zealand)
3. **Billing intervals match** (3 months for quarterly, 12 months for annual)
4. **Per-user vs flat-rate** is configured correctly in Stripe
5. **Webhook is working** to handle successful subscriptions

For technical support, refer to the main plugin documentation or contact the developer.

