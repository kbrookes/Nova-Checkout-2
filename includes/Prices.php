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

		$prices         = get_option( self::OPTION_NAME, $this->get_default_prices() );
		$support_prices = get_option( self::SUPPORT_OPTION_NAME, $this->get_default_support_prices() );

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
					<a href="#support-prices" class="nav-tab"><?php esc_html_e( 'Support Prices', 'nova-checkout' ); ?></a>
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

