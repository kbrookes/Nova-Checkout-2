<?php
/**
 * PHPStan bootstrap file.
 *
 * @package NovaCheckout
 */

// Define WordPress constants for PHPStan analysis.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}
