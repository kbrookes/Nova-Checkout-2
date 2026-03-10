<?php
/**
 * Settings class for Nova Checkout.
 *
 * @package NovaCheckout
 */

namespace NovaCheckout;

/**
 * Manages plugin settings and admin interface.
 */
class Settings {
	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'nova_checkout_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'nova-checkout-settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to WordPress admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Nova Checkout Settings', 'nova-checkout' ),
			__( 'Nova Checkout', 'nova-checkout' ),
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
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);

		// API Keys Section.
		add_settings_section(
			'nova_checkout_api_keys',
			__( 'Stripe API Keys', 'nova-checkout' ),
			array( $this, 'render_api_keys_section' ),
			self::PAGE_SLUG
		);

		// AU Secret Key.
		add_settings_field(
			'au_secret_key',
			__( 'Australia Secret Key', 'nova-checkout' ),
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			'nova_checkout_api_keys',
			array(
				'label_for'   => 'au_secret_key',
				'field_name'  => 'au_secret_key',
				'type'        => 'password',
				'description' => __( 'Stripe secret key for Australian account (sk_live_..., sk_test_..., rk_live_..., or rk_test_...)', 'nova-checkout' ),
			)
		);

		// NZ Secret Key.
		add_settings_field(
			'nz_secret_key',
			__( 'New Zealand Secret Key', 'nova-checkout' ),
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			'nova_checkout_api_keys',
			array(
				'label_for'   => 'nz_secret_key',
				'field_name'  => 'nz_secret_key',
				'type'        => 'password',
				'description' => __( 'Stripe secret key for New Zealand account (sk_live_..., sk_test_..., rk_live_..., or rk_test_...)', 'nova-checkout' ),
			)
		);

		// Webhook Secrets Section.
		add_settings_section(
			'nova_checkout_webhook_secrets',
			__( 'Stripe Webhook Secrets', 'nova-checkout' ),
			array( $this, 'render_webhook_secrets_section' ),
			self::PAGE_SLUG
		);

		// AU Webhook Secret.
		add_settings_field(
			'au_webhook_secret',
			__( 'Australia Webhook Secret', 'nova-checkout' ),
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			'nova_checkout_webhook_secrets',
			array(
				'label_for'   => 'au_webhook_secret',
				'field_name'  => 'au_webhook_secret',
				'type'        => 'password',
				'description' => __( 'Webhook signing secret for Australian account (whsec_...)', 'nova-checkout' ),
			)
		);

		// NZ Webhook Secret.
		add_settings_field(
			'nz_webhook_secret',
			__( 'New Zealand Webhook Secret', 'nova-checkout' ),
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			'nova_checkout_webhook_secrets',
			array(
				'label_for'   => 'nz_webhook_secret',
				'field_name'  => 'nz_webhook_secret',
				'type'        => 'password',
				'description' => __( 'Webhook signing secret for New Zealand account (whsec_...)', 'nova-checkout' ),
			)
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array<string, string>
	 */
	private function get_default_settings(): array {
		return array(
			'au_secret_key'     => '',
			'nz_secret_key'     => '',
			'au_webhook_secret' => '',
			'nz_webhook_secret' => '',
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return array<string, string> The sanitized settings.
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return $this->get_default_settings();
		}

		$sanitized = array();

		// Sanitize AU secret key.
		$sanitized['au_secret_key'] = $this->sanitize_secret_key( $input['au_secret_key'] ?? '' );

		// Sanitize NZ secret key.
		$sanitized['nz_secret_key'] = $this->sanitize_secret_key( $input['nz_secret_key'] ?? '' );

		// Sanitize AU webhook secret.
		$sanitized['au_webhook_secret'] = $this->sanitize_webhook_secret( $input['au_webhook_secret'] ?? '' );

		// Sanitize NZ webhook secret.
		$sanitized['nz_webhook_secret'] = $this->sanitize_webhook_secret( $input['nz_webhook_secret'] ?? '' );

		return $sanitized;
	}

	/**
	 * Sanitize a Stripe secret key.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string The sanitized secret key.
	 */
	private function sanitize_secret_key( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );

		if ( empty( $value ) ) {
			return '';
		}

		if ( ! is_valid_secret_key( $value ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'invalid_secret_key',
				__( 'Invalid Stripe secret key format. Must start with sk_live_, sk_test_, rk_live_, or rk_test_.', 'nova-checkout' ),
				'error'
			);
			return '';
		}

		return $value;
	}

	/**
	 * Sanitize a Stripe webhook secret.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string The sanitized webhook secret.
	 */
	private function sanitize_webhook_secret( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );

		if ( empty( $value ) ) {
			return '';
		}

		if ( ! is_valid_webhook_secret( $value ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'invalid_webhook_secret',
				__( 'Invalid Stripe webhook secret format. Must start with whsec_.', 'nova-checkout' ),
				'error'
			);
			return '';
		}

		return $value;
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

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PAGE_SLUG );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Settings', 'nova-checkout' ) );
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Webhook URLs', 'nova-checkout' ); ?></h2>
			<p><?php esc_html_e( 'Configure these webhook URLs in your Stripe dashboard:', 'nova-checkout' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Australia Webhook URL', 'nova-checkout' ); ?></th>
					<td>
						<code><?php echo esc_html( rest_url( 'nova/v1/stripe-webhook/au' ) ); ?></code>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'New Zealand Webhook URL', 'nova-checkout' ); ?></th>
					<td>
						<code><?php echo esc_html( rest_url( 'nova/v1/stripe-webhook/nz' ) ); ?></code>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render API keys section description.
	 *
	 * @return void
	 */
	public function render_api_keys_section(): void {
		echo '<p>';
		esc_html_e( 'Enter your Stripe API secret keys for each country. You can also set these via environment variables (AU_STRIPE_SECRET_KEY, NZ_STRIPE_SECRET_KEY).', 'nova-checkout' );
		echo '</p>';
	}

	/**
	 * Render webhook secrets section description.
	 *
	 * @return void
	 */
	public function render_webhook_secrets_section(): void {
		echo '<p>';
		esc_html_e( 'Enter your Stripe webhook signing secrets for each country. You can also set these via environment variables (AU_STRIPE_WEBHOOK_SECRET, NZ_STRIPE_WEBHOOK_SECRET).', 'nova-checkout' );
		echo '</p>';
	}

	/**
	 * Render a text/password field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_text_field( array $args ): void {
		$settings    = get_option( self::OPTION_NAME, $this->get_default_settings() );
		$field_name  = $args['field_name'] ?? '';
		$value       = $settings[ $field_name ] ?? '';
		$type        = $args['type'] ?? 'text';
		$description = $args['description'] ?? '';

		printf(
			'<input type="%s" id="%s" name="%s[%s]" value="%s" class="regular-text" autocomplete="off">',
			esc_attr( $type ),
			esc_attr( $field_name ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $field_name ),
			esc_attr( $value )
		);

		if ( ! empty( $description ) ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}
	}
}

