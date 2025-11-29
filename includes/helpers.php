<?php
/**
 * Helper functions for Nova Checkout.
 *
 * @package NovaCheckout
 */

namespace NovaCheckout;

/**
 * Sanitize a country code (AU or NZ).
 *
 * @param string $country The country code to sanitize.
 * @return string The sanitized country code (lowercase).
 */
function sanitize_country( string $country ): string {
	$country = strtolower( trim( $country ) );
	return in_array( $country, array( 'au', 'nz' ), true ) ? $country : '';
}

/**
 * Sanitize a billing period (quarterly or annual).
 *
 * @param string $period The billing period to sanitize.
 * @return string The sanitized billing period (lowercase).
 */
function sanitize_billing_period( string $period ): string {
	$period = strtolower( trim( $period ) );
	return in_array( $period, array( 'quarterly', 'annual' ), true ) ? $period : '';
}

/**
 * Sanitize a pricing tier (standard, professional, or ultimate).
 *
 * @param string $tier The pricing tier to sanitize.
 * @return string The sanitized tier (lowercase).
 */
function sanitize_tier( string $tier ): string {
	$tier = strtolower( trim( $tier ) );
	return in_array( $tier, array( 'standard', 'professional', 'ultimate' ), true ) ? $tier : '';
}

/**
 * Validate a Stripe price ID format.
 *
 * @param string $price_id The price ID to validate.
 * @return bool True if valid, false otherwise.
 */
function is_valid_price_id( string $price_id ): bool {
	// Stripe price IDs start with 'price_' followed by alphanumeric characters.
	return (bool) preg_match( '/^price_[a-zA-Z0-9]+$/', $price_id );
}

/**
 * Validate a Stripe secret key format.
 *
 * @param string $secret_key The secret key to validate.
 * @return bool True if valid, false otherwise.
 */
function is_valid_secret_key( string $secret_key ): bool {
	// Stripe secret keys start with 'sk_' (live) or 'sk_test_'.
	return (bool) preg_match( '/^sk_(live|test)_[a-zA-Z0-9]+$/', $secret_key );
}

/**
 * Validate a Stripe webhook secret format.
 *
 * @param string $webhook_secret The webhook secret to validate.
 * @return bool True if valid, false otherwise.
 */
function is_valid_webhook_secret( string $webhook_secret ): bool {
	// Stripe webhook secrets start with 'whsec_'.
	return (bool) preg_match( '/^whsec_[a-zA-Z0-9]+$/', $webhook_secret );
}

/**
 * Get a setting value from the plugin settings.
 *
 * @param string $key     The setting key.
 * @param mixed  $default The default value if the setting doesn't exist.
 * @return mixed The setting value.
 */
function get_setting( string $key, $default = '' ) {
	$settings = get_option( 'nova_checkout_settings', array() );
	return $settings[ $key ] ?? $default;
}

/**
 * Get a price ID from the price map.
 *
 * @param string $country The country code (au or nz).
 * @param string $tier    The pricing tier (standard, professional, or ultimate).
 * @param string $period  The billing period (quarterly or annual).
 * @return string The price ID, or empty string if not found.
 */
function get_price_id( string $country, string $tier, string $period ): string {
	$prices = get_option( 'nova_checkout_prices', array() );
	return $prices[ $country ][ $tier ][ $period ] ?? '';
}

/**
 * Get a support price ID from the support price map.
 *
 * @param string $country      The country code (au or nz).
 * @param string $support_tier The support tier (phone_standard, phone_professional, trainer, coach, specialist).
 * @param string $period       The billing period (quarterly or annual).
 * @return string The price ID, or empty string if not found.
 */
function get_support_price_id( string $country, string $support_tier, string $period ): string {
	$prices = get_option( 'nova_checkout_support_prices', array() );
	return $prices[ $country ][ $support_tier ][ $period ] ?? '';
}

/**
 * Log a message (for debugging, never log secrets).
 *
 * @param string $message The message to log.
 * @param string $level   The log level (error, warning, info, debug).
 * @return void
 */
function log_message( string $message, string $level = 'info' ): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( '[Nova Checkout] [%s] %s', strtoupper( $level ), $message ) );
	}
}

/**
 * Get the Stripe secret key for a country.
 *
 * Checks environment variables first, then falls back to WordPress options.
 *
 * @param string $country The country code (au or nz).
 * @return string The secret key, or empty string if not found.
 */
function get_stripe_secret_key( string $country ): string {
	$country = sanitize_country( $country );
	if ( empty( $country ) ) {
		return '';
	}

	// Check environment variable first.
	$env_key = strtoupper( $country ) . '_STRIPE_SECRET_KEY';
	$key     = getenv( $env_key );

	// Fall back to WordPress option.
	if ( empty( $key ) ) {
		$key = get_setting( $country . '_secret_key' );
	}

	return is_string( $key ) ? $key : '';
}

/**
 * Get the Stripe webhook secret for a country.
 *
 * Checks environment variables first, then falls back to WordPress options.
 *
 * @param string $country The country code (au or nz).
 * @return string The webhook secret, or empty string if not found.
 */
function get_stripe_webhook_secret( string $country ): string {
	$country = sanitize_country( $country );
	if ( empty( $country ) ) {
		return '';
	}

	// Check environment variable first.
	$env_key = strtoupper( $country ) . '_STRIPE_WEBHOOK_SECRET';
	$secret  = getenv( $env_key );

	// Fall back to WordPress option.
	if ( empty( $secret ) ) {
		$secret = get_setting( $country . '_webhook_secret' );
	}

	return is_string( $secret ) ? $secret : '';
}

/**
 * Sanitize and validate a positive integer.
 *
 * @param mixed $value The value to sanitize.
 * @param int   $min   The minimum allowed value.
 * @return int The sanitized integer, or 0 if invalid.
 */
function sanitize_positive_int( $value, int $min = 1 ): int {
	$int = absint( $value );
	return $int >= $min ? $int : 0;
}

/**
 * Create a safe error response (never expose sensitive data).
 *
 * @param string $message The user-friendly error message.
 * @param int    $code    The HTTP status code.
 * @return \WP_Error The error object.
 */
function create_error_response( string $message, int $code = 400 ): \WP_Error {
	return new \WP_Error(
		'nova_checkout_error',
		$message,
		array( 'status' => $code )
	);
}
