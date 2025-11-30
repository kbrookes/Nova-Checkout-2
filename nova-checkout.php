<?php
/**
 * Plugin Name: Nova Stripe Checkout v2
 * Plugin URI: https://github.com/kbrookes/Nova-Checkout-2
 * Description: WordPress plugin for Stripe Checkout subscriptions with per-seat pricing and AU/NZ account routing
 * Version: 2.4.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Kelsey Brookes
 * Author URI: https://siiteable.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nova-checkout
 * Domain Path: /languages
 * GitHub Plugin URI: kbrookes/Nova-Checkout-2
 * Primary Branch: main
 * Release Asset: true
 *
 * @package NovaCheckout
 */

namespace NovaCheckout;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'NOVA_CHECKOUT_VERSION', '2.4.0' );
define( 'NOVA_CHECKOUT_FILE', __FILE__ );
define( 'NOVA_CHECKOUT_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOVA_CHECKOUT_URL', plugin_dir_url( __FILE__ ) );
define( 'NOVA_CHECKOUT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(
	function ( $class ) {
		// Project-specific namespace prefix.
		$prefix = 'NovaCheckout\\';

		// Base directory for the namespace prefix.
		$base_dir = __DIR__ . '/includes/';

		// Does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Replace namespace separators with directory separators.
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// Load Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Load helper functions.
if ( file_exists( __DIR__ . '/includes/helpers.php' ) ) {
	require_once __DIR__ . '/includes/helpers.php';
}

/**
 * Main plugin class.
 */
class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Settings instance.
	 *
	 * @var Settings|null
	 */
	private ?Settings $settings = null;

	/**
	 * Prices instance.
	 *
	 * @var Prices|null
	 */
	private ?Prices $prices = null;

	/**
	 * REST instance.
	 *
	 * @var REST|null
	 */
	private ?REST $rest = null;

	/**
	 * Webhooks instance.
	 *
	 * @var Webhooks|null
	 */
	private ?Webhooks $webhooks = null;

	/**
	 * Shortcode instance.
	 *
	 * @var Shortcode|null
	 */
	private ?Shortcode $shortcode = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	private function init(): void {
		// Initialize components.
		$this->settings  = new Settings();
		$this->prices    = new Prices();
		$this->rest      = new REST();
		$this->webhooks  = new Webhooks();
		$this->shortcode = Shortcode::get_instance();
		$this->shortcode->init();

		// Register hooks.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'nova-checkout',
			false,
			dirname( NOVA_CHECKOUT_BASENAME ) . '/languages'
		);
	}

	/**
	 * Get Settings instance.
	 *
	 * @return Settings
	 */
	public function settings(): Settings {
		return $this->settings;
	}

	/**
	 * Get Prices instance.
	 *
	 * @return Prices
	 */
	public function prices(): Prices {
		return $this->prices;
	}

	/**
	 * Get REST instance.
	 *
	 * @return REST
	 */
	public function rest(): REST {
		return $this->rest;
	}

	/**
	 * Get Webhooks instance.
	 *
	 * @return Webhooks
	 */
	public function webhooks(): Webhooks {
		return $this->webhooks;
	}
}

/**
 * Plugin activation hook.
 *
 * @return void
 */
function activate_plugin(): void {
	// Set default options if they don't exist.
	if ( false === get_option( 'nova_checkout_settings' ) ) {
		add_option(
			'nova_checkout_settings',
			array(
				'au_secret_key'     => '',
				'nz_secret_key'     => '',
				'au_webhook_secret' => '',
				'nz_webhook_secret' => '',
			)
		);
	}

	if ( false === get_option( 'nova_checkout_prices' ) ) {
		add_option(
			'nova_checkout_prices',
			array(
				'au' => array(
					'standard'     => array(
						'quarterly' => '',
						'annual'    => '',
					),
					'professional' => array(
						'quarterly' => '',
						'annual'    => '',
					),
					'ultimate'     => array(
						'quarterly' => '',
						'annual'    => '',
					),
				),
				'nz' => array(
					'standard'     => array(
						'quarterly' => '',
						'annual'    => '',
					),
					'professional' => array(
						'quarterly' => '',
						'annual'    => '',
					),
					'ultimate'     => array(
						'quarterly' => '',
						'annual'    => '',
					),
				),
			)
		);
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate_plugin' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function deactivate_plugin(): void {
	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate_plugin' );

/**
 * Initialize the plugin.
 *
 * @return Plugin
 */
function nova_checkout(): Plugin {
	return Plugin::instance();
}

// Start the plugin.
nova_checkout();
