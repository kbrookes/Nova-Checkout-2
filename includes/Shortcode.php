<?php
/**
 * Shortcode handler for Nova Checkout form
 *
 * @package NovaCheckout
 */

declare(strict_types=1);

namespace NovaCheckout;

/**
 * Class Shortcode
 *
 * Handles the [nova_checkout] shortcode for displaying checkout forms
 */
class Shortcode {
	/**
	 * Initialize the shortcode
	 */
	public function init(): void {
		add_shortcode( 'nova_checkout', array( $this, 'render_checkout_form' ) );
	}

	/**
	 * Render the checkout form
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_checkout_form( array $atts ): string {
		$atts = shortcode_atts(
			array(
				'country'      => 'au',
				'success_url'  => home_url( '/checkout-success/' ),
				'cancel_url'   => '',
				'button_text'  => 'Continue to Checkout',
				'show_tiers'   => 'true',
				'default_tier' => '',
			),
			$atts,
			'nova_checkout'
		);

		// Sanitize attributes.
		$country      = sanitize_country( $atts['country'] );
		$success_url  = esc_url( $atts['success_url'] );
		$permalink    = get_permalink();
		$cancel_url   = ! empty( $atts['cancel_url'] ) ? esc_url( $atts['cancel_url'] ) : esc_url( $permalink ? $permalink : home_url() );
		$button_text  = esc_html( $atts['button_text'] );
		$show_tiers   = filter_var( $atts['show_tiers'], FILTER_VALIDATE_BOOLEAN );
		$default_tier = sanitize_tier( $atts['default_tier'] );

		// Enqueue inline styles.
		$this->enqueue_styles();

		// Start output buffering.
		ob_start();
		?>
		<div class="nova-checkout-form-wrapper">
			<form class="nova-checkout-form" data-country="<?php echo esc_attr( $country ); ?>" data-success-url="<?php echo esc_attr( $success_url ); ?>" data-cancel-url="<?php echo esc_attr( $cancel_url ); ?>">
				
				<?php if ( $show_tiers ) : ?>
				<!-- Tier Selection -->
				<div class="nova-form-group">
					<label class="nova-label">Select Your Plan</label>
					
					<label class="nova-tier-option" data-tier="standard">
						<input type="radio" name="tier" value="standard" <?php checked( $default_tier, 'standard' ); ?> required>
						<div class="nova-tier-content">
							<strong>Standard</strong>
							<span class="nova-tier-badge nova-tier-standard">STANDARD</span>
							<div class="nova-tier-description">Perfect for small teams getting started</div>
						</div>
					</label>

					<label class="nova-tier-option" data-tier="professional">
						<input type="radio" name="tier" value="professional" <?php checked( $default_tier, 'professional' ); ?> required>
						<div class="nova-tier-content">
							<strong>Professional</strong>
							<span class="nova-tier-badge nova-tier-professional">PROFESSIONAL</span>
							<div class="nova-tier-description">For growing teams with advanced needs</div>
						</div>
					</label>

					<label class="nova-tier-option" data-tier="ultimate">
						<input type="radio" name="tier" value="ultimate" <?php checked( $default_tier, 'ultimate' ); ?> required>
						<div class="nova-tier-content">
							<strong>Ultimate</strong>
							<span class="nova-tier-badge nova-tier-ultimate">ULTIMATE</span>
							<div class="nova-tier-description">Enterprise-grade features and support</div>
						</div>
					</label>
				</div>
				<?php else : ?>
				<input type="hidden" name="tier" value="<?php echo esc_attr( $default_tier ); ?>">
				<?php endif; ?>

				<!-- Billing Period -->
				<div class="nova-form-group">
					<label for="nova-billing-period" class="nova-label">Billing Period</label>
					<select id="nova-billing-period" name="billing_period" class="nova-select" required>
						<option value="">Select billing period...</option>
						<option value="quarterly">Quarterly</option>
						<option value="annual">Annual</option>
					</select>
				</div>

				<!-- Number of Users -->
				<div class="nova-form-group">
					<label for="nova-users" class="nova-label">Number of Users</label>
					<input 
						type="number" 
						id="nova-users" 
						name="users" 
						class="nova-input"
						min="1" 
						max="1000" 
						value="5" 
						required
					>
					<small class="nova-help-text">Minimum 1 user, maximum 1000 users</small>
				</div>

				<!-- Error Message -->
				<div class="nova-error" style="display: none;"></div>

				<!-- Submit Button -->
				<button type="submit" class="nova-submit-button">
					<?php echo esc_html( $button_text ); ?> →
				</button>
			</form>
		</div>

		<script>
		(function() {
			const form = document.querySelector('.nova-checkout-form');
			if (!form) return;

			// Tier selection visual feedback
			const tierOptions = form.querySelectorAll('.nova-tier-option');
			tierOptions.forEach(option => {
				option.addEventListener('click', function() {
					tierOptions.forEach(opt => opt.classList.remove('selected'));
					this.classList.add('selected');
					this.querySelector('input[type="radio"]').checked = true;
				});
			});

			// Form submission
			form.addEventListener('submit', async function(e) {
				e.preventDefault();
				
				const button = form.querySelector('.nova-submit-button');
				const errorDiv = form.querySelector('.nova-error');
				const originalButtonText = button.textContent;
				
				// Get form values
				const tier = form.querySelector('input[name="tier"]:checked')?.value || form.querySelector('input[name="tier"]')?.value;
				const billingPeriod = form.querySelector('[name="billing_period"]').value;
				const users = parseInt(form.querySelector('[name="users"]').value);
				const country = form.dataset.country;
				const successUrl = form.dataset.successUrl;
				const cancelUrl = form.dataset.cancelUrl;
				
				// Validate
				if (!tier || !billingPeriod || !users) {
					showError('Please fill in all fields');
					return;
				}
				
				// Disable button
				button.disabled = true;
				button.textContent = 'Creating checkout session...';
				errorDiv.style.display = 'none';
				
				try {
					const response = await fetch('/wp-json/nova/v1/checkout', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({
							country: country,
							tier: tier,
							billing_period: billingPeriod,
							users: users,
							success_url: successUrl,
							cancel_url: cancelUrl
						})
					});
					
					const data = await response.json();
					
					if (!response.ok) {
						throw new Error(data.message || 'Failed to create checkout session');
					}
					
					if (data.url) {
						window.location.href = data.url;
					} else {
						throw new Error('No checkout URL returned');
					}
					
				} catch (error) {
					console.error('Checkout error:', error);
					showError(error.message || 'An error occurred. Please try again.');
					button.disabled = false;
					button.textContent = originalButtonText;
				}
			});
			
			function showError(message) {
				const errorDiv = form.querySelector('.nova-error');
				errorDiv.textContent = message;
				errorDiv.style.display = 'block';
			}
		})();
		</script>
		<?php
		$output = ob_get_clean();
		return $output ? $output : '';
	}

	/**
	 * Enqueue inline styles for the checkout form
	 */
	private function enqueue_styles(): void {
		static $styles_enqueued = false;

		if ( $styles_enqueued ) {
			return;
		}

		$styles_enqueued = true;

		?>
		<style>
		.nova-checkout-form-wrapper {
			max-width: 600px;
			margin: 0 auto;
		}
		.nova-checkout-form {
			background: #fff;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		.nova-form-group {
			margin-bottom: 20px;
		}
		.nova-label {
			display: block;
			margin-bottom: 8px;
			font-weight: 600;
			color: #333;
		}
		.nova-select,
		.nova-input {
			width: 100%;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 16px;
			box-sizing: border-box;
		}
		.nova-tier-option {
			display: block;
			padding: 15px;
			border: 2px solid #e0e0e0;
			border-radius: 6px;
			margin-bottom: 10px;
			cursor: pointer;
			transition: all 0.2s;
		}
		.nova-tier-option:hover {
			border-color: #4CAF50;
			background: #f9f9f9;
		}
		.nova-tier-option.selected {
			border-color: #4CAF50;
			background: #f0f8f0;
		}
		.nova-tier-option input[type="radio"] {
			margin-right: 10px;
		}
		.nova-tier-content {
			display: inline-block;
			width: calc(100% - 30px);
		}
		.nova-tier-badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: 600;
			margin-left: 10px;
		}
		.nova-tier-standard {
			background: #2196F3;
			color: white;
		}
		.nova-tier-professional {
			background: #9C27B0;
			color: white;
		}
		.nova-tier-ultimate {
			background: #FF9800;
			color: white;
		}
		.nova-tier-description {
			margin-top: 8px;
			color: #666;
			font-size: 14px;
		}
		.nova-help-text {
			display: block;
			margin-top: 5px;
			color: #666;
			font-size: 14px;
		}
		.nova-error {
			background: #ffebee;
			color: #c62828;
			padding: 12px;
			border-radius: 4px;
			margin-bottom: 20px;
		}
		.nova-submit-button {
			width: 100%;
			padding: 15px;
			background: #4CAF50;
			color: white;
			border: none;
			border-radius: 4px;
			font-size: 18px;
			font-weight: 600;
			cursor: pointer;
			transition: background 0.2s;
		}
		.nova-submit-button:hover {
			background: #45a049;
		}
		.nova-submit-button:disabled {
			background: #ccc;
			cursor: not-allowed;
		}
		</style>
		<?php
	}

	/**
	 * Get instance.
	 *
	 * @return Shortcode
	 */
	public static function get_instance(): Shortcode {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance;
	}
}

