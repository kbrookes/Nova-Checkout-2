# Contributing to Nova Stripe Checkout v2

Thank you for considering contributing to Nova Stripe Checkout v2! This document outlines the guidelines and standards for contributing.

## Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/nova-checkout-2.git
   cd nova-checkout-2
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set up environment variables**
   ```bash
   cp .env.example .env
   # Edit .env with your Stripe test keys
   ```

4. **Install in WordPress**
   - Symlink or copy to `wp-content/plugins/nova-checkout/`
   - Activate the plugin

## Code Standards

### PHP Requirements

- **PHP Version:** 8.1 or higher
- **WordPress Version:** 6.0 or higher
- **Coding Standards:** WordPress Coding Standards
- **Static Analysis:** PHPStan Level 7

### Code Style

- Use **strict types** in all PHP files
- Follow **WordPress Coding Standards** (WPCS)
- Add **comprehensive docblocks** for all classes, methods, and functions
- Use **early returns** to reduce nesting
- Prefer **explicit types** over mixed types
- Keep functions **small and focused**
- Extract complex logic into **helper functions**

### Example

```php
<?php
/**
 * Example function with proper documentation.
 *
 * @param string $input The input to process.
 * @return string The processed output.
 */
function process_input( string $input ): string {
    // Early return for invalid input.
    if ( empty( $input ) ) {
        return '';
    }

    // Process and return.
    return sanitize_text_field( $input );
}
```

## Testing Your Changes

Before submitting a pull request, run all quality checks:

```bash
# Run all checks
composer test

# Or run individually
composer lint      # PHP syntax check
composer phpcs     # Coding standards
composer phpstan   # Static analysis
composer phpcpd    # Copy/paste detection
composer audit     # Security audit
```

**All checks must pass before submitting a PR.**

## Pull Request Process

1. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**
   - Write clean, documented code
   - Follow the code standards above
   - Add inline comments for complex logic

3. **Test your changes**
   - Run all quality checks (`composer test`)
   - Test manually in WordPress
   - Test with Stripe test mode

4. **Commit your changes**
   ```bash
   git add .
   git commit -m "Add feature: description of your changes"
   ```

5. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request**
   - Provide a clear description of the changes
   - Reference any related issues
   - Ensure CI checks pass

## Commit Message Guidelines

- Use clear, descriptive commit messages
- Start with a verb in present tense (Add, Fix, Update, Remove)
- Keep the first line under 72 characters
- Add details in the body if needed

**Examples:**
```
Add support for custom metadata in checkout sessions

Fix webhook signature validation for NZ account

Update PHPStan configuration to level 7

Remove deprecated helper function
```

## Security

- **Never commit secrets** (API keys, webhook secrets, etc.)
- **Validate all inputs** before processing
- **Sanitize all outputs** before displaying
- **Never log sensitive data** (API keys, customer data, etc.)
- **Use webhook signature verification** for all webhook endpoints
- **Report security issues privately** to the maintainers

## What to Contribute

### Good First Issues

- Documentation improvements
- Code comments and docblocks
- Bug fixes
- Test coverage improvements

### Feature Requests

Before working on a new feature:
1. Open an issue to discuss the feature
2. Wait for maintainer approval
3. Follow the PR process above

### Bug Reports

When reporting bugs, include:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages (if any)

## Code Review Process

1. Maintainers will review your PR
2. Address any requested changes
3. Once approved, your PR will be merged
4. Your contribution will be credited in the release notes

## Questions?

If you have questions about contributing, please open an issue with the "question" label.

## License

By contributing, you agree that your contributions will be licensed under the GPL v2 or later license.

## Thank You!

Your contributions make this project better for everyone. Thank you for taking the time to contribute! 🎉

