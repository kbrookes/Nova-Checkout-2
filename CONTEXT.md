# Nova Stripe Checkout v2 — CONTEXT

## Project Overview

**Goal:** WordPress plugin that exposes a REST endpoint to create Stripe Checkout Sessions for **subscriptions** with **per-seat quantity**, choosing **AU or NZ Stripe account** based on form data. Front-end is Bricks (or any form) making a POST to `/wp-json/nova/v1/checkout`.

## Must-haves

- ✅ Use **Stripe Checkout** (not Elements) with `mode: subscription`
- ✅ One line item: plan price, each with `quantity = users`
- ✅ Subscription can be quarterly or annual
- ✅ **AU vs NZ** routing by using different **Stripe Secret Keys** (from settings/env)
- ✅ Maintain a **price ID map** per country & billing (quarterly/annual)
- ✅ Make implementing and managing the **price ID map** as easy as possible in the Settings of the plugin
- ✅ Provide **webhook** endpoint to receive Stripe events (checkout completed, subscription updates)
- ✅ **No WooCommerce, no FluentCart**
- ✅ **Composer** for `stripe/stripe-php` only; keep deps minimal
- ✅ Plugin will be installed and updated via GitUpdater; needs to be compatible with GitUpdater and contain all files (including vendor files); cannot be installed via composer
- ✅ WordPress Coding Standards (PHPCS) compliance
- ✅ PHP 8.1+ compatible
- ✅ Sanitize/validate all inputs
- ✅ Never log secrets or return raw errors
- ✅ Ready for **Git Updater** delivery (GitHub-hosted)
- ✅ CI: lint, phpcs, phpstan, duplication checks, composer audit

## Non-goals

- No on-site card forms
- No multi-gateway switching beyond AU/NZ Stripe accounts
- No heavy framework dependencies
- No WooCommerce integration
- No FluentCart integration

## Architecture

### Core Classes
- `Settings` - Admin settings page and option management
- `Prices` - Price ID mapping for AU/NZ accounts
- `REST` - API endpoint for creating checkout sessions
- `Webhooks` - Stripe webhook event handling
- `helpers.php` - Utility functions

### API Endpoints
- `POST /wp-json/nova/v1/checkout` - Create Stripe checkout session
- `POST /wp-json/nova/v1/stripe-webhook` - Handle Stripe webhooks

### Configuration
- Environment variables for API keys (recommended)
- WordPress options for fallback configuration
- Admin settings page under Settings → Nova Checkout

## Quality Standards

- **Code Quality:** PHP 8.1+, WordPress Coding Standards, PHPStan level 7
- **Security:** Input sanitization, secret key protection, webhook signature validation
- **Testing:** CI/CD with GitHub Actions, automated quality checks
- **Documentation:** Comprehensive docblocks, README, contributing guidelines

## Dev Guardrails

- Don't introduce new deps without discussion
- Keep functions small and pure; extract helpers
- Prefer early returns; explicit types
- Add docblocks & inline reasoning where non-obvious
- All code changes must pass CI (php -l, PHPCS, PHPStan, phpcpd)
- For any public method, add brief usage notes in docblocks
