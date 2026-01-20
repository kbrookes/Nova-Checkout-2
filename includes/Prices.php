<?php
/**
 * Prices class for Nova Checkout.
 *
 * @package NovaCheckout
 */

namespace NovaCheckout;

/**
 * Manages Stripe price ID mappings for different countries and billing periods.
 */
class Prices {
	/**
	 * Prices option name.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'nova_checkout_prices';

	/**
	 * Support prices option name.
	 *
	 * @var string
	 */
	private const SUPPORT_OPTION_NAME = 'nova_checkout_support_prices';

	/**
	 * Support descriptions option name.
	 *
	 * @var string
	 */
	private const SUPPORT_DESCRIPTIONS_OPTION_NAME = 'nova_checkout_support_descriptions';

	/**
	 * Product monthly costs option name.
	 *
	 * @var string
	 */
	private const PRODUCT_MONTHLY_COSTS_OPTION_NAME = 'nova_checkout_product_monthly_costs';

	/**
	 * Support monthly costs option name.
	 *
	 * @var string
	 */
	private const SUPPORT_MONTHLY_COSTS_OPTION_NAME = 'nova_checkout_support_monthly_costs';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'nova-checkout-prices';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets for the prices page.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		// Add inline styles for the prices table.
		wp_add_inline_style(
			'wp-admin',
			'
			.nova-prices-tabs { margin: 20px 0; border-bottom: 1px solid #ccc; }
			.nova-prices-tabs button { background: #f0f0f1; border: 1px solid #ccc; border-bottom: none; padding: 10px 20px; margin-right: 5px; cursor: pointer; font-size: 14px; }
			.nova-prices-tabs button.active { background: #fff; font-weight: 600; }
			.nova-prices-tab-content { display: none; }
			.nova-prices-tab-content.active { display: block; }
			.nova-prices-table { width: 100%; max-width: 900px; border-collapse: collapse; margin: 20px 0; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
			.nova-prices-table th { background: #f0f0f1; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
			.nova-prices-table td { padding: 12px; border-bottom: 1px solid #ddd; }
			.nova-prices-table tr:last-child td { border-bottom: none; }
			.nova-prices-table input[type="text"] { width: 100%; max-width: 400px; }
			.nova-price-status { display: inline-block; width: 20px; height: 20px; line-height: 20px; text-align: center; margin-left: 8px; }
			.nova-price-status.configured { color: #46b450; }
			.nova-price-status.empty { color: #dc3232; }
			.nova-tier-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; margin-right: 8px; }
			.nova-tier-standard { background: #e3f2fd; color: #1976d2; }
			.nova-tier-professional { background: #f3e5f5; color: #7b1fa2; }
			.nova-tier-ultimate { background: #fff3e0; color: #f57c00; }
			.nova-stripe-link { font-size: 12px; color: #635bff; text-decoration: none; }
			.nova-stripe-link:hover { text-decoration: underline; }
			.nova-help-box { background: #f0f6fc; border-left: 4px solid #0969da; padding: 12px 16px; margin: 20px 0; }
			.nova-help-box h4 { margin: 0 0 8px 0; color: #0969da; }
			'
		);

		// Add inline script for tabs.
		wp_add_inline_script(
			'jquery',
			"
			jQuery(document).ready(function($) {
				// Handle main section tabs (Product/Support)
				$('.nav-tab-wrapper a').on('click', function(e) {
					e.preventDefault();
					var target = $(this).attr('href').substring(1);
					$('.nav-tab-wrapper a').removeClass('nav-tab-active');
					$(this).addClass('nav-tab-active');
					$('.nova-section-content').hide();
					$('#' + target).show();
				});

				// Handle country tabs within sections
				$('.nova-prices-tabs button').on('click', function() {
					var tab = $(this).data('tab');
					var parent = $(this).closest('.nova-section-content');
					parent.find('.nova-prices-tabs button').removeClass('active');
					$(this).addClass('active');
					parent.find('.nova-prices-tab-content').removeClass('active');
					$('#' + tab).addClass('active');
				});
			});
			"
		);
	}

	/**
	 * Add settings page to WordPress admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'options-general.php',
			__( 'Nova Checkout Prices', 'nova-checkout' ),
			__( 'Nova Prices', 'nova-checkout' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::PAGE_SLUG,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_prices' ),
				'default'           => $this->get_default_prices(),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			self::SUPPORT_OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_support_prices' ),
				'default'           => $this->get_default_support_prices(),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			self::SUPPORT_DESCRIPTIONS_OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_support_descriptions' ),
				'default'           => $this->get_default_support_descriptions(),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			self::PRODUCT_MONTHLY_COSTS_OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_product_monthly_costs' ),
				'default'           => $this->get_default_product_monthly_costs(),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			self::SUPPORT_MONTHLY_COSTS_OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_support_monthly_costs' ),
				'default'           => $this->get_default_support_monthly_costs(),
			)
		);
	}

	/**
	 * Get default prices structure.
	 *
	 * @return array<string, array<string, array<string, string>>>
	 */
	private function get_default_prices(): array {
		return array(
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
		);
	}

	/**
	 * Sanitize prices before saving.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return array<string, array<string, array<string, string>>> The sanitized prices.
	 */
	public function sanitize_prices( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->get_default_prices();
		}

		$sanitized = $this->get_default_prices();

		// Sanitize each price ID.
		foreach ( array( 'au', 'nz' ) as $country ) {
			foreach ( array( 'standard', 'professional', 'ultimate' ) as $tier ) {
				foreach ( array( 'quarterly', 'annual' ) as $period ) {
					$value                                     = $input[ $country ][ $tier ][ $period ] ?? '';
					$sanitized[ $country ][ $tier ][ $period ] = $this->sanitize_price_id( $value, $country, $tier, $period );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a Stripe price ID.
	 *
	 * @param mixed  $value   The value to sanitize.
	 * @param string $country The country code.
	 * @param string $tier    The pricing tier.
	 * @param string $period  The billing period.
	 * @return string The sanitized price ID.
	 */
	private function sanitize_price_id( $value, string $country, string $tier, string $period ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );

		if ( empty( $value ) ) {
			return '';
		}

		if ( ! is_valid_price_id( $value ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'invalid_price_id',
				sprintf(
					/* translators: 1: country code, 2: tier, 3: billing period */
					__( 'Invalid Stripe price ID format for %1$s %2$s %3$s. Must start with price_.', 'nova-checkout' ),
					strtoupper( $country ),
					ucfirst( $tier ),
					$period
				),
				'error'
			);
			return '';
		}

		return $value;
	}

	/**
	 * Get a price ID for a specific country, tier, and billing period.
	 *
	 * @param string $country The country code (au or nz).
	 * @param string $tier    The pricing tier (standard, professional, or ultimate).
	 * @param string $period  The billing period (quarterly or annual).
	 * @return string The price ID, or empty string if not found.
	 */
	public function get_price_id( string $country, string $tier, string $period ): string {
		$country = sanitize_country( $country );
		$tier    = sanitize_tier( $tier );
		$period  = sanitize_billing_period( $period );

		if ( empty( $country ) || empty( $tier ) || empty( $period ) ) {
			return '';
		}

		$prices = get_option( self::OPTION_NAME, $this->get_default_prices() );
		return $prices[ $country ][ $tier ][ $period ] ?? '';
	}

	/**
	 * Get default support prices structure.
	 *
	 * @return array<string, array<string, array<string, string>>>
	 */
	private function get_default_support_prices(): array {
		return array(
			'au' => array(
				'phone_standard'     => array(
					'quarterly' => '',
					'annual'    => '',
				),
				'phone_professional' => array(
					'quarterly' => '',
					'annual'    => '',
				),
				'trainer'            => array(
					'quarterly' => '',
					'annual'    => '',
				),
				'coach'              => array(
					'quarterly' => '',
					'annual'    => '',
				),
				'specialist'         => array(
					'quarterly' => '',
					'annual'    => '',
				),
			),
			'nz' => array(
				'phone_standard'     => array(
					'quarterly' => '',
					'annual'    => '',
				),
				'phone_professional' => array(
					'quarterly' => '',
					'annual'    => '',
				),
				'trainer'            => array(
					'quarterly' => '',
					'annual'    => '',
				),
				'coach'              => array(
					'quarterly' => '',
					'annual'    => '',
				),
				'specialist'         => array(
					'quarterly' => '',
					'annual'    => '',
				),
			),
		);
	}

	/**
	 * Get default support descriptions structure.
	 *
	 * @return array<string, string>
	 */
	public function get_default_support_descriptions(): array {
		return array(
			'self_service'       => 'Access to knowledge base and community forums',
			'phone_standard'     => 'Email + phone support during business hours',
			'phone_professional' => 'Email + phone support during business hours',
			'trainer'            => 'Phone support + dedicated trainer for onboarding',
			'coach'              => 'Trainer + ongoing coaching and best practices',
			'specialist'         => 'Coach + dedicated specialist for advanced needs',
		);
	}

	/**
	 * Get default product monthly costs structure.
	 *
	 * @return array<string, array<string, array<string, string>>>
	 */
	public function get_default_product_monthly_costs(): array {
		return array(
			'au' => array(
				'standard'     => array(
					'quarterly' => '29',
					'annual'    => '27',
				),
				'professional' => array(
					'quarterly' => '39',
					'annual'    => '37',
				),
				'ultimate'     => array(
					'quarterly' => '49',
					'annual'    => '47',
				),
			),
			'nz' => array(
				'standard'     => array(
					'quarterly' => '32',
					'annual'    => '30',
				),
				'professional' => array(
					'quarterly' => '43',
					'annual'    => '41',
				),
				'ultimate'     => array(
					'quarterly' => '54',
					'annual'    => '52',
				),
			),
		);
	}

	/**
	 * Sanitize support descriptions before saving.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return array<string, string> The sanitized support descriptions.
	 */
	public function sanitize_support_descriptions( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->get_default_support_descriptions();
		}

		$sanitized = array();
		$defaults  = $this->get_default_support_descriptions();

		foreach ( $defaults as $key => $default_value ) {
			$sanitized[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $default_value;
		}

		return $sanitized;
	}

	/**
	 * Sanitize product monthly costs before saving.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return array<string, array<string, array<string, string>>> The sanitized product monthly costs.
	 */
	public function sanitize_product_monthly_costs( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->get_default_product_monthly_costs();
		}

		$sanitized = array();
		$defaults  = $this->get_default_product_monthly_costs();

		foreach ( array( 'au', 'nz' ) as $country ) {
			foreach ( array( 'standard', 'professional', 'ultimate' ) as $tier ) {
				foreach ( array( 'quarterly', 'annual' ) as $period ) {
					$value = $input[ $country ][ $tier ][ $period ] ?? '';
					// Sanitize as a decimal number.
					$sanitized[ $country ][ $tier ][ $period ] = is_numeric( $value ) ? (string) abs( (float) $value ) : $defaults[ $country ][ $tier ][ $period ];
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Get a support description.
	 *
	 * @param string $support_tier The support tier key.
	 * @return string The description.
	 */
	public function get_support_description( string $support_tier ): string {
		$descriptions = get_option( self::SUPPORT_DESCRIPTIONS_OPTION_NAME, $this->get_default_support_descriptions() );
		return $descriptions[ $support_tier ] ?? '';
	}

	/**
	 * Get product monthly cost.
	 *
	 * @param string $country The country code (au or nz).
	 * @param string $tier    The tier (standard, professional, ultimate).
	 * @param string $period  The billing period (quarterly or annual).
	 * @return string The monthly cost.
	 */
	public function get_product_monthly_cost( string $country, string $tier, string $period ): string {
		$costs = get_option( self::PRODUCT_MONTHLY_COSTS_OPTION_NAME, $this->get_default_product_monthly_costs() );
		return $costs[ $country ][ $tier ][ $period ] ?? '';
	}

	/**
	 * Get default support monthly costs structure.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_default_support_monthly_costs(): array {
		return array(
			'au' => array(
				'phone_standard'     => '18',
				'phone_professional' => '9',
				'trainer'            => '49',
				'coach'              => '74',
				'specialist'         => '99',
			),
			'nz' => array(
				'phone_standard'     => '20',
				'phone_professional' => '10',
				'trainer'            => '55',
				'coach'              => '83',
				'specialist'         => '110',
			),
		);
	}

	/**
	 * Sanitize support monthly costs before saving.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return array<string, array<string, string>> The sanitized support monthly costs.
	 */
	public function sanitize_support_monthly_costs( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->get_default_support_monthly_costs();
		}

		$sanitized = array();
		$defaults  = $this->get_default_support_monthly_costs();

		foreach ( array( 'au', 'nz' ) as $country ) {
			foreach ( array( 'phone_standard', 'phone_professional', 'trainer', 'coach', 'specialist' ) as $support_tier ) {
				$value = $input[ $country ][ $support_tier ] ?? '';
				// Sanitize as a decimal number.
				$sanitized[ $country ][ $support_tier ] = is_numeric( $value ) ? (string) abs( (float) $value ) : $defaults[ $country ][ $support_tier ];
			}
		}

		return $sanitized;
	}

	/**
	 * Get support monthly cost.
	 *
	 * @param string $country      The country code (au or nz).
	 * @param string $support_tier The support tier key.
	 * @return string The monthly cost.
	 */
	public function get_support_monthly_cost( string $country, string $support_tier ): string {
		$costs = get_option( self::SUPPORT_MONTHLY_COSTS_OPTION_NAME, $this->get_default_support_monthly_costs() );
		return $costs[ $country ][ $support_tier ] ?? '';
	}

	/**
	 * Sanitize support prices before saving.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return array<string, array<string, array<string, string>>> The sanitized support prices.
	 */
	public function sanitize_support_prices( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->get_default_support_prices();
		}

		$sanitized = $this->get_default_support_prices();

		// Sanitize each support price ID.
		foreach ( array( 'au', 'nz' ) as $country ) {
			foreach ( array( 'phone_standard', 'phone_professional', 'trainer', 'coach', 'specialist' ) as $support_tier ) {
				foreach ( array( 'quarterly', 'annual' ) as $period ) {
					$value = $input[ $country ][ $support_tier ][ $period ] ?? '';
					$sanitized[ $country ][ $support_tier ][ $period ] = $this->sanitize_support_price_id( $value, $country, $support_tier, $period );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a support price ID.
	 *
	 * @param mixed  $value        The value to sanitize.
	 * @param string $country      The country code.
	 * @param string $support_tier The support tier.
	 * @param string $period       The billing period.
	 * @return string The sanitized price ID.
	 */
	private function sanitize_support_price_id( $value, string $country, string $support_tier, string $period ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );

		if ( empty( $value ) ) {
			return '';
		}

		if ( ! is_valid_price_id( $value ) ) {
			add_settings_error(
				self::SUPPORT_OPTION_NAME,
				'invalid_support_price_id',
				sprintf(
					/* translators: 1: country code, 2: support tier, 3: billing period */
					__( 'Invalid Stripe price ID format for %1$s %2$s %3$s support. Must start with price_.', 'nova-checkout' ),
					strtoupper( $country ),
					ucfirst( str_replace( '_', ' ', $support_tier ) ),
					$period
				),
				'error'
			);
			return '';
		}

		return $value;
	}

	/**
	 * Get a support price ID for a specific country, support tier, and billing period.
	 *
	 * @param string $country      The country code (au or nz).
	 * @param string $support_tier The support tier (phone_standard, phone_professional, trainer, coach, specialist).
	 * @param string $period       The billing period (quarterly or annual).
	 * @return string The price ID, or empty string if not found.
	 */
	public function get_support_price_id( string $country, string $support_tier, string $period ): string {
		$country = sanitize_country( $country );
		$period  = sanitize_billing_period( $period );

		$valid_support_tiers = array( 'phone_standard', 'phone_professional', 'trainer', 'coach', 'specialist' );
		if ( ! in_array( $support_tier, $valid_support_tiers, true ) ) {
			return '';
		}

		if ( empty( $country ) || empty( $period ) ) {
			return '';
		}

		$prices = get_option( self::SUPPORT_OPTION_NAME, $this->get_default_support_prices() );
		return $prices[ $country ][ $support_tier ][ $period ] ?? '';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$prices                = get_option( self::OPTION_NAME, $this->get_default_prices() );
		$support_prices        = get_option( self::SUPPORT_OPTION_NAME, $this->get_default_support_prices() );
		$support_descriptions  = get_option( self::SUPPORT_DESCRIPTIONS_OPTION_NAME, $this->get_default_support_descriptions() );
		$product_monthly_costs = get_option( self::PRODUCT_MONTHLY_COSTS_OPTION_NAME, $this->get_default_product_monthly_costs() );
		$support_monthly_costs = get_option( self::SUPPORT_MONTHLY_COSTS_OPTION_NAME, $this->get_default_support_monthly_costs() );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="nova-help-box">
				<h4><?php esc_html_e( '📋 Quick Setup Guide', 'nova-checkout' ); ?></h4>
				<ol style="margin: 8px 0 0 20px;">
					<li><?php esc_html_e( 'Create your subscription prices in Stripe Dashboard for each tier and billing period', 'nova-checkout' ); ?></li>
					<li><?php esc_html_e( 'Copy the price IDs (starting with "price_") from Stripe', 'nova-checkout' ); ?></li>
					<li><?php esc_html_e( 'Paste them into the corresponding fields below', 'nova-checkout' ); ?></li>
					<li><?php esc_html_e( 'Save your changes', 'nova-checkout' ); ?></li>
				</ol>
				<p style="margin: 12px 0 0 0;">
					<a href="https://dashboard.stripe.com/products" target="_blank" class="nova-stripe-link">
						<?php esc_html_e( '→ Open Stripe Dashboard to create prices', 'nova-checkout' ); ?>
					</a>
					&nbsp;|&nbsp;
					<a href="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'SUPPORT-OPTIONS-SETUP.md' ); ?>" target="_blank" class="nova-stripe-link">
						<?php esc_html_e( '→ Support Options Setup Guide', 'nova-checkout' ); ?>
					</a>
				</p>
			</div>

			<form action="options.php" method="post">
				<?php settings_fields( self::PAGE_SLUG ); ?>

				<h2 class="nav-tab-wrapper">
					<a href="#product-prices" class="nav-tab nav-tab-active"><?php esc_html_e( 'Product Prices', 'nova-checkout' ); ?></a>
					<a href="#product-monthly-costs" class="nav-tab"><?php esc_html_e( 'Product Monthly Costs', 'nova-checkout' ); ?></a>
					<a href="#support-prices" class="nav-tab"><?php esc_html_e( 'Support Prices', 'nova-checkout' ); ?></a>
					<a href="#support-monthly-costs" class="nav-tab"><?php esc_html_e( 'Support Monthly Costs', 'nova-checkout' ); ?></a>
					<a href="#support-descriptions" class="nav-tab"><?php esc_html_e( 'Support Descriptions', 'nova-checkout' ); ?></a>
				</h2>

				<div id="product-prices" class="nova-section-content">
					<div class="nova-prices-tabs">
						<button type="button" class="active" data-tab="tab-product-au"><?php esc_html_e( '🇦🇺 Australia', 'nova-checkout' ); ?></button>
						<button type="button" data-tab="tab-product-nz"><?php esc_html_e( '🇳🇿 New Zealand', 'nova-checkout' ); ?></button>
					</div>

					<div id="tab-product-au" class="nova-prices-tab-content active">
						<?php $this->render_prices_table( 'au', $prices ); ?>
					</div>

					<div id="tab-product-nz" class="nova-prices-tab-content">
						<?php $this->render_prices_table( 'nz', $prices ); ?>
					</div>
				</div>

				<div id="product-monthly-costs" class="nova-section-content" style="display: none;">
					<h3><?php esc_html_e( 'Product Monthly Costs', 'nova-checkout' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Set the monthly cost displayed to customers for each product tier and billing period. Annual subscriptions typically have a lower monthly cost than quarterly.', 'nova-checkout' ); ?>
					</p>

					<div class="nova-prices-tabs">
						<button type="button" class="active" data-tab="tab-monthly-au"><?php esc_html_e( '🇦🇺 Australia', 'nova-checkout' ); ?></button>
						<button type="button" data-tab="tab-monthly-nz"><?php esc_html_e( '🇳🇿 New Zealand', 'nova-checkout' ); ?></button>
					</div>

					<div id="tab-monthly-au" class="nova-prices-tab-content active">
						<?php $this->render_product_monthly_costs_table( 'au', $product_monthly_costs ); ?>
					</div>

					<div id="tab-monthly-nz" class="nova-prices-tab-content">
						<?php $this->render_product_monthly_costs_table( 'nz', $product_monthly_costs ); ?>
					</div>
				</div>

				<div id="support-prices" class="nova-section-content" style="display: none;">
					<div class="nova-prices-tabs">
						<button type="button" class="active" data-tab="tab-support-au"><?php esc_html_e( '🇦🇺 Australia', 'nova-checkout' ); ?></button>
						<button type="button" data-tab="tab-support-nz"><?php esc_html_e( '🇳🇿 New Zealand', 'nova-checkout' ); ?></button>
					</div>

					<div id="tab-support-au" class="nova-prices-tab-content active">
						<?php $this->render_support_prices_table( 'au', $support_prices ); ?>
					</div>

					<div id="tab-support-nz" class="nova-prices-tab-content">
						<?php $this->render_support_prices_table( 'nz', $support_prices ); ?>
					</div>
				</div>

				<div id="support-monthly-costs" class="nova-section-content" style="display: none;">
					<h3><?php esc_html_e( 'Support Monthly Costs', 'nova-checkout' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Set the monthly cost displayed to customers for each support level. These costs do not vary by billing period.', 'nova-checkout' ); ?>
					</p>

					<div class="nova-prices-tabs">
						<button type="button" class="active" data-tab="tab-support-monthly-au"><?php esc_html_e( '🇦🇺 Australia', 'nova-checkout' ); ?></button>
						<button type="button" data-tab="tab-support-monthly-nz"><?php esc_html_e( '🇳🇿 New Zealand', 'nova-checkout' ); ?></button>
					</div>

					<div id="tab-support-monthly-au" class="nova-prices-tab-content active">
						<?php $this->render_support_monthly_costs_table( 'au', $support_monthly_costs ); ?>
					</div>

					<div id="tab-support-monthly-nz" class="nova-prices-tab-content">
						<?php $this->render_support_monthly_costs_table( 'nz', $support_monthly_costs ); ?>
					</div>
				</div>

				<div id="support-descriptions" class="nova-section-content" style="display: none;">
					<h3><?php esc_html_e( 'Support Level Descriptions', 'nova-checkout' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Customize the descriptions shown to customers for each support level in the checkout form.', 'nova-checkout' ); ?>
					</p>

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="support_desc_self_service">
										<span class="nova-tier-badge nova-tier-standard"><?php esc_html_e( 'Self-Service', 'nova-checkout' ); ?></span>
									</label>
								</th>
								<td>
									<input
										type="text"
										id="support_desc_self_service"
										name="<?php echo esc_attr( self::SUPPORT_DESCRIPTIONS_OPTION_NAME ); ?>[self_service]"
										value="<?php echo esc_attr( $support_descriptions['self_service'] ?? '' ); ?>"
										class="large-text"
										placeholder="<?php esc_attr_e( 'Enter description for self-service support', 'nova-checkout' ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="support_desc_phone_standard">
										<span class="nova-tier-badge nova-tier-standard"><?php esc_html_e( 'Phone (Standard)', 'nova-checkout' ); ?></span>
									</label>
								</th>
								<td>
									<input
										type="text"
										id="support_desc_phone_standard"
										name="<?php echo esc_attr( self::SUPPORT_DESCRIPTIONS_OPTION_NAME ); ?>[phone_standard]"
										value="<?php echo esc_attr( $support_descriptions['phone_standard'] ?? '' ); ?>"
										class="large-text"
										placeholder="<?php esc_attr_e( 'Enter description for phone support (standard tier)', 'nova-checkout' ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="support_desc_phone_professional">
										<span class="nova-tier-badge nova-tier-professional"><?php esc_html_e( 'Phone (Professional)', 'nova-checkout' ); ?></span>
									</label>
								</th>
								<td>
									<input
										type="text"
										id="support_desc_phone_professional"
										name="<?php echo esc_attr( self::SUPPORT_DESCRIPTIONS_OPTION_NAME ); ?>[phone_professional]"
										value="<?php echo esc_attr( $support_descriptions['phone_professional'] ?? '' ); ?>"
										class="large-text"
										placeholder="<?php esc_attr_e( 'Enter description for phone support (professional tier)', 'nova-checkout' ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="support_desc_trainer">
										<span class="nova-tier-badge nova-tier-professional"><?php esc_html_e( 'Trainer', 'nova-checkout' ); ?></span>
									</label>
								</th>
								<td>
									<input
										type="text"
										id="support_desc_trainer"
										name="<?php echo esc_attr( self::SUPPORT_DESCRIPTIONS_OPTION_NAME ); ?>[trainer]"
										value="<?php echo esc_attr( $support_descriptions['trainer'] ?? '' ); ?>"
										class="large-text"
										placeholder="<?php esc_attr_e( 'Enter description for trainer support', 'nova-checkout' ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="support_desc_coach">
										<span class="nova-tier-badge nova-tier-ultimate"><?php esc_html_e( 'Coach', 'nova-checkout' ); ?></span>
									</label>
								</th>
								<td>
									<input
										type="text"
										id="support_desc_coach"
										name="<?php echo esc_attr( self::SUPPORT_DESCRIPTIONS_OPTION_NAME ); ?>[coach]"
										value="<?php echo esc_attr( $support_descriptions['coach'] ?? '' ); ?>"
										class="large-text"
										placeholder="<?php esc_attr_e( 'Enter description for coach support', 'nova-checkout' ); ?>"
									/>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="support_desc_specialist">
										<span class="nova-tier-badge nova-tier-ultimate"><?php esc_html_e( 'Specialist', 'nova-checkout' ); ?></span>
									</label>
								</th>
								<td>
									<input
										type="text"
										id="support_desc_specialist"
										name="<?php echo esc_attr( self::SUPPORT_DESCRIPTIONS_OPTION_NAME ); ?>[specialist]"
										value="<?php echo esc_attr( $support_descriptions['specialist'] ?? '' ); ?>"
										class="large-text"
										placeholder="<?php esc_attr_e( 'Enter description for specialist support', 'nova-checkout' ); ?>"
									/>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php submit_button( __( 'Save Price IDs', 'nova-checkout' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render prices table for a specific country.
	 *
	 * @param string               $country The country code (au or nz).
	 * @param array<string, mixed> $prices  The prices array.
	 * @return void
	 */
	private function render_prices_table( string $country, array $prices ): void {
		$country_name = 'au' === $country ? __( 'Australia', 'nova-checkout' ) : __( 'New Zealand', 'nova-checkout' );
		$tiers        = array(
			'standard'     => array(
				'label' => __( 'Standard', 'nova-checkout' ),
				'class' => 'nova-tier-standard',
			),
			'professional' => array(
				'label' => __( 'Professional', 'nova-checkout' ),
				'class' => 'nova-tier-professional',
			),
			'ultimate'     => array(
				'label' => __( 'Ultimate', 'nova-checkout' ),
				'class' => 'nova-tier-ultimate',
			),
		);

		?>
		<table class="nova-prices-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Pricing Tier', 'nova-checkout' ); ?></th>
					<th><?php esc_html_e( 'Quarterly Price ID', 'nova-checkout' ); ?></th>
					<th><?php esc_html_e( 'Annual Price ID', 'nova-checkout' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tiers as $tier_key => $tier_data ) : ?>
					<?php
					$quarterly_value = $prices[ $country ][ $tier_key ]['quarterly'] ?? '';
					$annual_value    = $prices[ $country ][ $tier_key ]['annual'] ?? '';
					?>
					<tr>
						<td>
							<span class="nova-tier-badge <?php echo esc_attr( $tier_data['class'] ); ?>">
								<?php echo esc_html( $tier_data['label'] ); ?>
							</span>
						</td>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $country ); ?>][<?php echo esc_attr( $tier_key ); ?>][quarterly]"
								value="<?php echo esc_attr( $quarterly_value ); ?>"
								placeholder="price_..."
								class="regular-text"
							/>
							<span class="nova-price-status <?php echo ! empty( $quarterly_value ) ? 'configured' : 'empty'; ?>">
								<?php echo ! empty( $quarterly_value ) ? '✓' : '○'; ?>
							</span>
						</td>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $country ); ?>][<?php echo esc_attr( $tier_key ); ?>][annual]"
								value="<?php echo esc_attr( $annual_value ); ?>"
								placeholder="price_..."
								class="regular-text"
							/>
							<span class="nova-price-status <?php echo ! empty( $annual_value ) ? 'configured' : 'empty'; ?>">
								<?php echo ! empty( $annual_value ) ? '✓' : '○'; ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="description">
			<?php
			printf(
				/* translators: %s: country name */
				esc_html__( 'Enter Stripe price IDs for %s subscriptions. Price IDs start with "price_" and can be found in your Stripe Dashboard.', 'nova-checkout' ),
				esc_html( $country_name )
			);
			?>
			<br>
			<a href="https://dashboard.stripe.com/products" target="_blank" class="nova-stripe-link">
				<?php esc_html_e( 'View prices in Stripe Dashboard →', 'nova-checkout' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render product monthly costs table for a specific country.
	 *
	 * @param string               $country The country code (au or nz).
	 * @param array<string, mixed> $costs   The monthly costs array.
	 * @return void
	 */
	private function render_product_monthly_costs_table( string $country, array $costs ): void {
		$country_name = 'au' === $country ? __( 'Australia', 'nova-checkout' ) : __( 'New Zealand', 'nova-checkout' );
		$currency     = 'au' === $country ? 'AUD' : 'NZD';
		$tiers        = array(
			'standard'     => array(
				'label' => __( 'Standard', 'nova-checkout' ),
				'class' => 'nova-tier-standard',
			),
			'professional' => array(
				'label' => __( 'Professional', 'nova-checkout' ),
				'class' => 'nova-tier-professional',
			),
			'ultimate'     => array(
				'label' => __( 'Ultimate', 'nova-checkout' ),
				'class' => 'nova-tier-ultimate',
			),
		);

		?>
		<table class="nova-prices-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Pricing Tier', 'nova-checkout' ); ?></th>
					<th><?php esc_html_e( 'Quarterly (per month)', 'nova-checkout' ); ?></th>
					<th><?php esc_html_e( 'Annual (per month)', 'nova-checkout' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tiers as $tier_key => $tier_data ) : ?>
					<?php
					$quarterly_value = $costs[ $country ][ $tier_key ]['quarterly'] ?? '';
					$annual_value    = $costs[ $country ][ $tier_key ]['annual'] ?? '';
					?>
					<tr>
						<td>
							<span class="nova-tier-badge <?php echo esc_attr( $tier_data['class'] ); ?>">
								<?php echo esc_html( $tier_data['label'] ); ?>
							</span>
						</td>
						<td>
							<input
								type="number"
								step="0.01"
								min="0"
								name="<?php echo esc_attr( self::PRODUCT_MONTHLY_COSTS_OPTION_NAME ); ?>[<?php echo esc_attr( $country ); ?>][<?php echo esc_attr( $tier_key ); ?>][quarterly]"
								value="<?php echo esc_attr( $quarterly_value ); ?>"
								placeholder="29.00"
								class="small-text"
							/>
							<span class="description"><?php echo esc_html( $currency ); ?></span>
						</td>
						<td>
							<input
								type="number"
								step="0.01"
								min="0"
								name="<?php echo esc_attr( self::PRODUCT_MONTHLY_COSTS_OPTION_NAME ); ?>[<?php echo esc_attr( $country ); ?>][<?php echo esc_attr( $tier_key ); ?>][annual]"
								value="<?php echo esc_attr( $annual_value ); ?>"
								placeholder="27.00"
								class="small-text"
							/>
							<span class="description"><?php echo esc_html( $currency ); ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="description">
			<?php
			printf(
				/* translators: %s: country name */
				esc_html__( 'Enter the monthly cost displayed to customers for %s subscriptions. Annual subscriptions typically have a lower monthly cost than quarterly.', 'nova-checkout' ),
				esc_html( $country_name )
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the support monthly costs table for a specific country.
	 *
	 * @param string               $country The country code (au or nz).
	 * @param array<string, mixed> $costs   The monthly costs array.
	 * @return void
	 */
	private function render_support_monthly_costs_table( string $country, array $costs ): void {
		$country_name   = 'au' === $country ? __( 'Australia', 'nova-checkout' ) : __( 'New Zealand', 'nova-checkout' );
		$currency       = 'au' === $country ? 'AUD' : 'NZD';
		$support_levels = array(
			'phone_standard'     => array(
				'label' => __( 'Phone (Standard)', 'nova-checkout' ),
				'class' => 'nova-tier-standard',
			),
			'phone_professional' => array(
				'label' => __( 'Phone (Professional)', 'nova-checkout' ),
				'class' => 'nova-tier-professional',
			),
			'trainer'            => array(
				'label' => __( 'Trainer', 'nova-checkout' ),
				'class' => 'nova-tier-professional',
			),
			'coach'              => array(
				'label' => __( 'Coach', 'nova-checkout' ),
				'class' => 'nova-tier-ultimate',
			),
			'specialist'         => array(
				'label' => __( 'Specialist', 'nova-checkout' ),
				'class' => 'nova-tier-ultimate',
			),
		);

		?>
		<table class="nova-prices-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Support Level', 'nova-checkout' ); ?></th>
					<th><?php esc_html_e( 'Monthly Cost', 'nova-checkout' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $support_levels as $level_key => $level_data ) : ?>
					<?php
					$monthly_value = $costs[ $country ][ $level_key ] ?? '';
					?>
					<tr>
						<td>
							<span class="nova-tier-badge <?php echo esc_attr( $level_data['class'] ); ?>">
								<?php echo esc_html( $level_data['label'] ); ?>
							</span>
						</td>
						<td>
							<input
								type="number"
								step="0.01"
								min="0"
								name="<?php echo esc_attr( self::SUPPORT_MONTHLY_COSTS_OPTION_NAME ); ?>[<?php echo esc_attr( $country ); ?>][<?php echo esc_attr( $level_key ); ?>]"
								value="<?php echo esc_attr( $monthly_value ); ?>"
								placeholder="<?php echo esc_attr( $this->get_default_support_monthly_costs()[ $country ][ $level_key ] ?? '0.00' ); ?>"
								class="small-text"
							/>
							<span class="description"><?php echo esc_html( $currency ); ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="description">
			<?php
			printf(
				/* translators: %s: country name */
				esc_html__( 'Enter the monthly cost displayed to customers for %s support options. Support pricing does not vary by billing period.', 'nova-checkout' ),
				esc_html( $country_name )
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render support prices table for a specific country.
	 *
	 * @param string               $country        The country code (au or nz).
	 * @param array<string, mixed> $support_prices The support prices array.
	 * @return void
	 */
	private function render_support_prices_table( string $country, array $support_prices ): void {
		$country_name  = 'au' === $country ? __( 'Australia', 'nova-checkout' ) : __( 'New Zealand', 'nova-checkout' );
		$support_tiers = array(
			'phone_standard'     => array(
				'label' => __( 'Phone (Standard)', 'nova-checkout' ),
				'class' => 'nova-tier-standard',
			),
			'phone_professional' => array(
				'label' => __( 'Phone (Professional)', 'nova-checkout' ),
				'class' => 'nova-tier-professional',
			),
			'trainer'            => array(
				'label' => __( 'Trainer', 'nova-checkout' ),
				'class' => 'nova-tier-professional',
			),
			'coach'              => array(
				'label' => __( 'Coach', 'nova-checkout' ),
				'class' => 'nova-tier-ultimate',
			),
			'specialist'         => array(
				'label' => __( 'Specialist', 'nova-checkout' ),
				'class' => 'nova-tier-ultimate',
			),
		);

		?>
		<table class="nova-prices-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Support Tier', 'nova-checkout' ); ?></th>
					<th><?php esc_html_e( 'Quarterly Price ID', 'nova-checkout' ); ?></th>
					<th><?php esc_html_e( 'Annual Price ID', 'nova-checkout' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $support_tiers as $tier_key => $tier_data ) : ?>
					<?php
					$quarterly_value = $support_prices[ $country ][ $tier_key ]['quarterly'] ?? '';
					$annual_value    = $support_prices[ $country ][ $tier_key ]['annual'] ?? '';
					?>
					<tr>
						<td>
							<span class="nova-tier-badge <?php echo esc_attr( $tier_data['class'] ); ?>">
								<?php echo esc_html( $tier_data['label'] ); ?>
							</span>
						</td>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( self::SUPPORT_OPTION_NAME ); ?>[<?php echo esc_attr( $country ); ?>][<?php echo esc_attr( $tier_key ); ?>][quarterly]"
								value="<?php echo esc_attr( $quarterly_value ); ?>"
								placeholder="price_..."
								class="regular-text"
							/>
							<span class="nova-price-status <?php echo ! empty( $quarterly_value ) ? 'configured' : 'empty'; ?>">
								<?php echo ! empty( $quarterly_value ) ? '✓' : '○'; ?>
							</span>
						</td>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( self::SUPPORT_OPTION_NAME ); ?>[<?php echo esc_attr( $country ); ?>][<?php echo esc_attr( $tier_key ); ?>][annual]"
								value="<?php echo esc_attr( $annual_value ); ?>"
								placeholder="price_..."
								class="regular-text"
							/>
							<span class="nova-price-status <?php echo ! empty( $annual_value ) ? 'configured' : 'empty'; ?>">
								<?php echo ! empty( $annual_value ) ? '✓' : '○'; ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="description">
			<?php
			printf(
				/* translators: %s: country name */
				esc_html__( 'Enter Stripe price IDs for %s support subscriptions. See the Support Options Setup Guide for pricing details.', 'nova-checkout' ),
				esc_html( $country_name )
			);
			?>
			<br>
			<a href="https://dashboard.stripe.com/products" target="_blank" class="nova-stripe-link">
				<?php esc_html_e( 'View prices in Stripe Dashboard →', 'nova-checkout' ); ?>
			</a>
		</p>
		<?php
	}
}

