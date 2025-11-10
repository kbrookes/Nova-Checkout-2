<?php
/**
 * REST API class for Nova Checkout.
 *
 * @package NovaCheckout
 */

namespace NovaCheckout;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

/**
 * Handles REST API endpoints for creating Stripe checkout sessions.
 */
class REST {
	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'nova/v1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/checkout',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_checkout_session' ),
				'permission_callback' => '__return_true', // Public endpoint.
				'args'                => $this->get_checkout_args(),
			)
		);
	}

	/**
	 * Get checkout endpoint arguments schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_checkout_args(): array {
		return array(
			'country'        => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_country',
				'validate_callback' => function ( $value ) {
					return in_array( strtolower( $value ), array( 'au', 'nz' ), true );
				},
				'description'       => __( 'Country code (au or nz)', 'nova-checkout' ),
			),
			'tier'           => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_tier',
				'validate_callback' => function ( $value ) {
					return in_array( strtolower( $value ), array( 'standard', 'professional', 'ultimate' ), true );
				},
				'description'       => __( 'Pricing tier (standard, professional, or ultimate)', 'nova-checkout' ),
			),
			'billing_period' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_billing_period',
				'validate_callback' => function ( $value ) {
					return in_array( strtolower( $value ), array( 'quarterly', 'annual' ), true );
				},
				'description'       => __( 'Billing period (quarterly or annual)', 'nova-checkout' ),
			),
			'users'          => array(
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => function ( $value ) {
					return sanitize_positive_int( $value, 1 );
				},
				'validate_callback' => function ( $value ) {
					return is_numeric( $value ) && (int) $value >= 1;
				},
				'description'       => __( 'Number of users (quantity)', 'nova-checkout' ),
			),
			'success_url'    => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => function ( $value ) {
					return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
				},
				'description'       => __( 'URL to redirect to after successful checkout', 'nova-checkout' ),
			),
			'cancel_url'     => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'validate_callback' => function ( $value ) {
					return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
				},
				'description'       => __( 'URL to redirect to if checkout is cancelled', 'nova-checkout' ),
			),
			'customer_email' => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => function ( $value ) {
					return empty( $value ) || is_email( $value );
				},
				'description'       => __( 'Customer email address (optional)', 'nova-checkout' ),
			),
			'metadata'       => array(
				'required'          => false,
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize_metadata' ),
				'description'       => __( 'Additional metadata to attach to the session (optional)', 'nova-checkout' ),
			),
		);
	}

	/**
	 * Sanitize metadata object.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array<string, string> The sanitized metadata.
	 */
	public function sanitize_metadata( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $key => $val ) {
			// Stripe metadata keys and values must be strings.
			$sanitized[ sanitize_key( $key ) ] = sanitize_text_field( (string) $val );
		}

		return $sanitized;
	}

	/**
	 * Create a Stripe checkout session.
	 *
	 * Usage: POST /wp-json/nova/v1/checkout
	 * Body: {
	 *   "country": "au",
	 *   "tier": "professional",
	 *   "billing_period": "quarterly",
	 *   "users": 5,
	 *   "success_url": "https://example.com/success",
	 *   "cancel_url": "https://example.com/cancel",
	 *   "customer_email": "customer@example.com",
	 *   "metadata": {"source": "website"}
	 * }
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error The response or error.
	 */
	public function create_checkout_session( \WP_REST_Request $request ) {
		// Get and validate parameters.
		$country        = $request->get_param( 'country' );
		$tier           = $request->get_param( 'tier' );
		$billing_period = $request->get_param( 'billing_period' );
		$users          = $request->get_param( 'users' );
		$success_url    = $request->get_param( 'success_url' );
		$cancel_url     = $request->get_param( 'cancel_url' );
		$customer_email = $request->get_param( 'customer_email' );
		$metadata       = $request->get_param( 'metadata' ) ?? array();

		// Get Stripe secret key for the country.
		$secret_key = get_stripe_secret_key( $country );
		if ( empty( $secret_key ) ) {
			log_message( "Missing Stripe secret key for country: {$country}", 'error' );
			return create_error_response(
				__( 'Configuration error. Please contact support.', 'nova-checkout' ),
				500
			);
		}

		// Get price ID for the country, tier, and billing period.
		$price_id = get_price_id( $country, $tier, $billing_period );
		if ( empty( $price_id ) ) {
			log_message( "Missing price ID for {$country} {$tier} {$billing_period}", 'error' );
			return create_error_response(
				__( 'Configuration error. Please contact support.', 'nova-checkout' ),
				500
			);
		}

		// Set Stripe API key.
		Stripe::setApiKey( $secret_key );

		// Prepare session parameters.
		$session_params = array(
			'mode'        => 'subscription',
			'line_items'  => array(
				array(
					'price'    => $price_id,
					'quantity' => $users,
				),
			),
			'success_url' => $success_url,
			'cancel_url'  => $cancel_url,
			'metadata'    => array_merge(
				$metadata,
				array(
					'country'        => $country,
					'billing_period' => $billing_period,
					'users'          => (string) $users,
				)
			),
		);

		// Add customer email if provided.
		if ( ! empty( $customer_email ) ) {
			$session_params['customer_email'] = $customer_email;
		}

		// Create the checkout session.
		try {
			$session = Session::create( $session_params );

			log_message( "Checkout session created: {$session->id} for {$country} {$billing_period} with {$users} users", 'info' );

			return new \WP_REST_Response(
				array(
					'success'     => true,
					'session_id'  => $session->id,
					'session_url' => $session->url,
				),
				200
			);
		} catch ( ApiErrorException $e ) {
			// Log the actual error but don't expose it to the user.
			log_message( "Stripe API error: {$e->getMessage()}", 'error' );

			return create_error_response(
				__( 'Unable to create checkout session. Please try again.', 'nova-checkout' ),
				500
			);
		}
	}
}
