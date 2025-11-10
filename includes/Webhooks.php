<?php
/**
 * Webhooks class for Nova Checkout.
 *
 * @package NovaCheckout
 */

namespace NovaCheckout;

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

/**
 * Handles Stripe webhook events.
 */
class Webhooks {
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
	 * Register REST API routes for webhooks.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Webhook endpoint for AU.
		register_rest_route(
			self::NAMESPACE,
			'/stripe-webhook/au',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_au_webhook' ),
				'permission_callback' => '__return_true', // Validation done via signature.
			)
		);

		// Webhook endpoint for NZ.
		register_rest_route(
			self::NAMESPACE,
			'/stripe-webhook/nz',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_nz_webhook' ),
				'permission_callback' => '__return_true', // Validation done via signature.
			)
		);
	}

	/**
	 * Handle webhook for Australia.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error The response or error.
	 */
	public function handle_au_webhook( \WP_REST_Request $request ) {
		return $this->handle_webhook( $request, 'au' );
	}

	/**
	 * Handle webhook for New Zealand.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error The response or error.
	 */
	public function handle_nz_webhook( \WP_REST_Request $request ) {
		return $this->handle_webhook( $request, 'nz' );
	}

	/**
	 * Handle a Stripe webhook event.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @param string           $country The country code (au or nz).
	 * @return \WP_REST_Response|\WP_Error The response or error.
	 */
	private function handle_webhook( \WP_REST_Request $request, string $country ) {
		// Get the webhook secret for this country.
		$webhook_secret = get_stripe_webhook_secret( $country );
		if ( empty( $webhook_secret ) ) {
			log_message( "Missing webhook secret for country: {$country}", 'error' );
			return create_error_response(
				__( 'Webhook configuration error.', 'nova-checkout' ),
				500
			);
		}

		// Get the raw request body.
		$payload = $request->get_body();
		if ( empty( $payload ) ) {
			log_message( 'Empty webhook payload received', 'error' );
			return create_error_response(
				__( 'Invalid webhook payload.', 'nova-checkout' ),
				400
			);
		}

		// Get the Stripe signature header.
		$sig_header = $request->get_header( 'stripe-signature' );
		if ( empty( $sig_header ) ) {
			log_message( 'Missing Stripe signature header', 'error' );
			return create_error_response(
				__( 'Missing signature.', 'nova-checkout' ),
				400
			);
		}

		// Verify the webhook signature.
		try {
			$event = Webhook::constructEvent( $payload, $sig_header, $webhook_secret );
		} catch ( \UnexpectedValueException $e ) {
			log_message( "Invalid webhook payload: {$e->getMessage()}", 'error' );
			return create_error_response(
				__( 'Invalid payload.', 'nova-checkout' ),
				400
			);
		} catch ( SignatureVerificationException $e ) {
			log_message( "Invalid webhook signature: {$e->getMessage()}", 'error' );
			return create_error_response(
				__( 'Invalid signature.', 'nova-checkout' ),
				400
			);
		}

		// Process the event.
		$this->process_event( $event, $country );

		// Return success response.
		return new \WP_REST_Response(
			array( 'received' => true ),
			200
		);
	}

	/**
	 * Process a Stripe webhook event.
	 *
	 * @param \Stripe\Event $event   The Stripe event object.
	 * @param string        $country The country code.
	 * @return void
	 */
	private function process_event( $event, string $country ): void {
		$event_type = $event->type;

		log_message( "Processing webhook event: {$event_type} for {$country}", 'info' );

		// Handle different event types.
		switch ( $event_type ) {
			case 'checkout.session.completed':
				$this->handle_checkout_completed( $event->data->object, $country );
				break;

			case 'customer.subscription.created':
				$this->handle_subscription_created( $event->data->object, $country );
				break;

			case 'customer.subscription.updated':
				$this->handle_subscription_updated( $event->data->object, $country );
				break;

			case 'customer.subscription.deleted':
				$this->handle_subscription_deleted( $event->data->object, $country );
				break;

			case 'invoice.payment_succeeded':
				$this->handle_invoice_payment_succeeded( $event->data->object, $country );
				break;

			case 'invoice.payment_failed':
				$this->handle_invoice_payment_failed( $event->data->object, $country );
				break;

			default:
				log_message( "Unhandled webhook event type: {$event_type}", 'info' );
				break;
		}

		// Fire a generic action for custom handling.
		do_action( 'nova_checkout_webhook_event', $event, $country );
		do_action( "nova_checkout_webhook_{$event_type}", $event->data->object, $country );
	}

	/**
	 * Handle checkout.session.completed event.
	 *
	 * @param \Stripe\Checkout\Session $session The checkout session object.
	 * @param string                   $country The country code.
	 * @return void
	 */
	private function handle_checkout_completed( $session, string $country ): void {
		$session_id      = $session->id;
		$customer_id     = $session->customer ?? '';
		$subscription_id = $session->subscription ?? '';
		$customer_email  = $session->customer_email ?? '';

		log_message(
			"Checkout completed: Session {$session_id}, Customer {$customer_id}, Subscription {$subscription_id}",
			'info'
		);

		// Fire action for custom handling.
		do_action( 'nova_checkout_session_completed', $session, $country );
	}

	/**
	 * Handle customer.subscription.created event.
	 *
	 * @param \Stripe\Subscription $subscription The subscription object.
	 * @param string               $country      The country code.
	 * @return void
	 */
	private function handle_subscription_created( $subscription, string $country ): void {
		$subscription_id = $subscription->id;
		$customer_id     = $subscription->customer;
		$status          = $subscription->status;

		log_message(
			"Subscription created: {$subscription_id}, Customer {$customer_id}, Status {$status}",
			'info'
		);

		// Fire action for custom handling.
		do_action( 'nova_checkout_subscription_created', $subscription, $country );
	}

	/**
	 * Handle customer.subscription.updated event.
	 *
	 * @param \Stripe\Subscription $subscription The subscription object.
	 * @param string               $country      The country code.
	 * @return void
	 */
	private function handle_subscription_updated( $subscription, string $country ): void {
		$subscription_id = $subscription->id;
		$status          = $subscription->status;

		log_message( "Subscription updated: {$subscription_id}, Status {$status}", 'info' );

		// Fire action for custom handling.
		do_action( 'nova_checkout_subscription_updated', $subscription, $country );
	}

	/**
	 * Handle customer.subscription.deleted event.
	 *
	 * @param \Stripe\Subscription $subscription The subscription object.
	 * @param string               $country      The country code.
	 * @return void
	 */
	private function handle_subscription_deleted( $subscription, string $country ): void {
		$subscription_id = $subscription->id;

		log_message( "Subscription deleted: {$subscription_id}", 'info' );

		// Fire action for custom handling.
		do_action( 'nova_checkout_subscription_deleted', $subscription, $country );
	}

	/**
	 * Handle invoice.payment_succeeded event.
	 *
	 * @param \Stripe\Invoice $invoice The invoice object.
	 * @param string          $country The country code.
	 * @return void
	 */
	private function handle_invoice_payment_succeeded( $invoice, string $country ): void {
		$invoice_id      = $invoice->id;
		$subscription_id = $invoice->subscription ?? '';

		log_message( "Invoice payment succeeded: {$invoice_id}, Subscription {$subscription_id}", 'info' );

		// Fire action for custom handling.
		do_action( 'nova_checkout_invoice_payment_succeeded', $invoice, $country );
	}

	/**
	 * Handle invoice.payment_failed event.
	 *
	 * @param \Stripe\Invoice $invoice The invoice object.
	 * @param string          $country The country code.
	 * @return void
	 */
	private function handle_invoice_payment_failed( $invoice, string $country ): void {
		$invoice_id      = $invoice->id;
		$subscription_id = $invoice->subscription ?? '';

		log_message( "Invoice payment failed: {$invoice_id}, Subscription {$subscription_id}", 'warning' );

		// Fire action for custom handling.
		do_action( 'nova_checkout_invoice_payment_failed', $invoice, $country );
	}
}
