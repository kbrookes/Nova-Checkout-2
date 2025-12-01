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
				'country'                => 'au',
				'success_url'            => home_url( '/checkout-success/' ),
				'cancel_url'             => '',
				'button_text'            => 'Continue to Checkout',
				'show_tiers'             => 'true',
				'default_tier'           => '',
				'default_billing_period' => '',
				'default_users'          => '1',
				'use_url_params'         => 'true',
				'show_period_toggle'     => 'true',
			),
			$atts,
			'nova_checkout'
		);

		// Check URL parameters if enabled.
		$url_tier   = '';
		$url_period = '';
		if ( filter_var( $atts['use_url_params'], FILTER_VALIDATE_BOOLEAN ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url_tier = isset( $_GET['tier'] ) ? sanitize_tier( sanitize_text_field( wp_unslash( $_GET['tier'] ) ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url_period = isset( $_GET['period'] ) ? sanitize_billing_period( sanitize_text_field( wp_unslash( $_GET['period'] ) ) ) : '';
		}

		// Sanitize attributes.
		$country   = sanitize_country( $atts['country'] );
		$permalink = get_permalink();

		// Ensure URLs are absolute for Stripe API validation.
		$success_url = $atts['success_url'];
		if ( ! preg_match( '/^https?:\/\//', $success_url ) ) {
			$success_url = home_url( $success_url );
		}
		$success_url = esc_url( $success_url );

		$cancel_url = ! empty( $atts['cancel_url'] ) ? $atts['cancel_url'] : ( $permalink ? $permalink : home_url() );
		if ( ! preg_match( '/^https?:\/\//', $cancel_url ) ) {
			$cancel_url = home_url( $cancel_url );
		}
		$cancel_url = esc_url( $cancel_url );

		$button_text            = esc_html( $atts['button_text'] );
		$default_tier           = ! empty( $url_tier ) ? $url_tier : sanitize_tier( $atts['default_tier'] );
		$default_billing_period = ! empty( $url_period ) ? $url_period : sanitize_billing_period( $atts['default_billing_period'] );
		$default_users          = absint( $atts['default_users'] );
		$show_period_toggle     = filter_var( $atts['show_period_toggle'], FILTER_VALIDATE_BOOLEAN );

		// Enqueue inline styles.
		$this->enqueue_styles();

		// Start output buffering.
		ob_start();
		?>
		<div class="nova-checkout-form-wrapper">
			<form class="nova-checkout-form" data-country="<?php echo esc_attr( $country ); ?>" data-success-url="<?php echo esc_attr( $success_url ); ?>" data-cancel-url="<?php echo esc_attr( $cancel_url ); ?>">

				<!-- Billing Period -->
				<?php if ( $show_period_toggle ) : ?>
				<div class="nova-form-group nova-billing-period">
					<label class="nova-label">Billing Period</label>
					<div class="switch-wrapper">
						<input id="nova-quarterly" type="radio" name="billing_period" value="quarterly" <?php checked( $default_billing_period, 'quarterly' ); ?> <?php checked( empty( $default_billing_period ) ); ?> required>
						<input id="nova-yearly" type="radio" name="billing_period" value="annual" <?php checked( $default_billing_period, 'annual' ); ?> required>
						<label for="nova-quarterly">Quarterly</label>
						<label for="nova-yearly">Yearly</label>
						<span class="highlighter"></span>
					</div>
				</div>
				<?php else : ?>
				<div class="nova-form-group nova-billing-period">
					<label for="nova-billing-period" class="nova-label">Billing Period</label>
					<select id="nova-billing-period" name="billing_period" class="nova-select" required>
						<option value="">Select billing period...</option>
						<option value="quarterly" <?php selected( $default_billing_period, 'quarterly' ); ?>>Quarterly</option>
						<option value="annual" <?php selected( $default_billing_period, 'annual' ); ?>>Annual</option>
					</select>
				</div>
				<?php endif; ?>

				<!-- Product & Support Grid -->
				<div class="nova-selections-grid">
					<!-- Tier Selection -->
					<div class="nova-form-group">
					<label class="nova-label">Select Your Plan</label>

					<label class="nova-tier-option" data-tier="standard" data-monthly-cost-au-quarterly="<?php echo esc_attr( get_product_monthly_cost( 'au', 'standard', 'quarterly' ) ); ?>" data-monthly-cost-nz-quarterly="<?php echo esc_attr( get_product_monthly_cost( 'nz', 'standard', 'quarterly' ) ); ?>" data-monthly-cost-au-annual="<?php echo esc_attr( get_product_monthly_cost( 'au', 'standard', 'annual' ) ); ?>" data-monthly-cost-nz-annual="<?php echo esc_attr( get_product_monthly_cost( 'nz', 'standard', 'annual' ) ); ?>">
						<input type="radio" name="tier" value="standard" <?php checked( $default_tier, 'standard' ); ?> required>
						<div class="nova-tier-content">
							<strong>Act! Advantage Standard</strong>
							<span class="nova-tier-badge">STANDARD</span>
							<div class="nova-tier-description">Perfect for small teams getting started</div>
						</div>
					</label>

					<label class="nova-tier-option" data-tier="professional" data-monthly-cost-au-quarterly="<?php echo esc_attr( get_product_monthly_cost( 'au', 'professional', 'quarterly' ) ); ?>" data-monthly-cost-nz-quarterly="<?php echo esc_attr( get_product_monthly_cost( 'nz', 'professional', 'quarterly' ) ); ?>" data-monthly-cost-au-annual="<?php echo esc_attr( get_product_monthly_cost( 'au', 'professional', 'annual' ) ); ?>" data-monthly-cost-nz-annual="<?php echo esc_attr( get_product_monthly_cost( 'nz', 'professional', 'annual' ) ); ?>">
						<input type="radio" name="tier" value="professional" <?php checked( $default_tier, 'professional' ); ?> required>
						<div class="nova-tier-content">
							<strong>Act! Advantage Professional</strong>
							<span class="nova-tier-badge">PROFESSIONAL</span>
							<div class="nova-tier-description">For growing teams with advanced needs</div>
						</div>
					</label>

					<label class="nova-tier-option" data-tier="ultimate" data-monthly-cost-au-quarterly="<?php echo esc_attr( get_product_monthly_cost( 'au', 'ultimate', 'quarterly' ) ); ?>" data-monthly-cost-nz-quarterly="<?php echo esc_attr( get_product_monthly_cost( 'nz', 'ultimate', 'quarterly' ) ); ?>" data-monthly-cost-au-annual="<?php echo esc_attr( get_product_monthly_cost( 'au', 'ultimate', 'annual' ) ); ?>" data-monthly-cost-nz-annual="<?php echo esc_attr( get_product_monthly_cost( 'nz', 'ultimate', 'annual' ) ); ?>">
						<input type="radio" name="tier" value="ultimate" <?php checked( $default_tier, 'ultimate' ); ?> required>
						<div class="nova-tier-content">
							<strong>Act! Advantage Ultimate</strong>
							<span class="nova-tier-badge">ULTIMATE</span>
							<div class="nova-tier-description">Enterprise-grade features and support</div>
						</div>
					</label>
				</div>

				<!-- Support Level Selection -->
				<div class="nova-form-group">
					<label class="nova-label">Select Support Level</label>

					<label class="nova-support-option" data-support="self-service">
						<input type="radio" name="support_level" value="self-service" checked required>
						<div class="nova-support-content">
							<strong>Self-Service</strong>
							<span class="nova-support-badge nova-support-free">INCLUDED</span>
							<div class="nova-support-description"><?php echo esc_html( get_support_description( 'self_service' ) ); ?></div>
						</div>
					</label>

					<label class="nova-support-option" data-support="phone-standard" data-per-user="true" data-monthly-cost-au="18" data-monthly-cost-nz="20" data-max-users="2" data-tier="standard">
						<input type="radio" name="support_level" value="phone-standard" required>
						<div class="nova-support-content">
							<strong>Support + Phone (Standard)</strong>
							<span class="nova-support-badge nova-support-phone">PHONE</span>
							<div class="nova-support-description"><?php echo esc_html( get_support_description( 'phone_standard' ) ); ?></div>
							<div class="nova-support-pricing"></div>
						</div>
					</label>

					<label class="nova-support-option" data-support="phone-professional" data-per-user="true" data-monthly-cost-au="9" data-monthly-cost-nz="10" data-max-users="5" data-tier="professional">
						<input type="radio" name="support_level" value="phone-professional" required>
						<div class="nova-support-content">
							<strong>Support + Phone (Professional)</strong>
							<span class="nova-support-badge nova-support-phone">PHONE</span>
							<div class="nova-support-description"><?php echo esc_html( get_support_description( 'phone_professional' ) ); ?></div>
							<div class="nova-support-pricing"></div>
						</div>
					</label>

					<label class="nova-support-option" data-support="trainer" data-per-user="false" data-monthly-cost-au="49" data-monthly-cost-nz="55">
						<input type="radio" name="support_level" value="trainer" required>
						<div class="nova-support-content">
							<strong>Support + Trainer</strong>
							<span class="nova-support-badge nova-support-trainer">TRAINER</span>
							<div class="nova-support-description"><?php echo esc_html( get_support_description( 'trainer' ) ); ?></div>
							<div class="nova-support-pricing"></div>
						</div>
					</label>

					<label class="nova-support-option" data-support="coach" data-per-user="false" data-monthly-cost-au="74" data-monthly-cost-nz="83">
						<input type="radio" name="support_level" value="coach" required>
						<div class="nova-support-content">
							<strong>Support + Coach</strong>
							<span class="nova-support-badge nova-support-coach">COACH</span>
							<div class="nova-support-description"><?php echo esc_html( get_support_description( 'coach' ) ); ?></div>
							<div class="nova-support-pricing"></div>
						</div>
					</label>

					<label class="nova-support-option" data-support="specialist" data-per-user="false" data-monthly-cost-au="99" data-monthly-cost-nz="110">
						<input type="radio" name="support_level" value="specialist" required>
						<div class="nova-support-content">
							<strong>Support + Specialist</strong>
							<span class="nova-support-badge nova-support-specialist">SPECIALIST</span>
							<div class="nova-support-description"><?php echo esc_html( get_support_description( 'specialist' ) ); ?></div>
							<div class="nova-support-pricing"></div>
						</div>
					</label>
				</div>

				<!-- Pricing Summary -->
				<div class="nova-pricing-summary">
					<div class="nova-pricing-total">
						<span class="nova-pricing-label">Total</span>
						<span class="nova-pricing-amount">$<span id="nova-total-amount">0.00</span> per month</span>
					</div>
					<div class="nova-pricing-period">Billed <span id="nova-billing-text">Quarterly</span></div>
				</div>
				</div><!-- End .nova-selections-grid -->

				<!-- Number of Users - Slider (Desktop) -->
				<div class="nova-slider-container">
					<div class="nova-slider-wrapper">
						<div class="nova-slider-value"><span id="nova-slider-value"><?php echo esc_html( (string) $default_users ); ?></span> Users</div>
						<input
							type="range"
							id="nova-users-slider"
							class="nova-slider"
							min="1"
							max="50"
							step="1"
							value="<?php echo esc_attr( (string) $default_users ); ?>"
						>
						<div class="nova-slider-labels">
							<span>1 User</span>
							<span>50 Users</span>
						</div>
					</div>
				</div>

				<!-- Number of Users - Input (Mobile) -->
				<div class="nova-form-group nova-input-container">
					<label for="nova-users" class="nova-label">Number of Users</label>
					<input
						type="number"
						id="nova-users"
						name="users"
						class="nova-input"
						min="1"
						max="1000"
						value="<?php echo esc_attr( (string) $default_users ); ?>"
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

			const country = form.dataset.country;

			// Sync slider with input field
			const slider = document.getElementById('nova-users-slider');
			const usersInput = document.getElementById('nova-users');
			const sliderValue = document.getElementById('nova-slider-value');

			if (slider && usersInput && sliderValue) {
				// Update input when slider changes
				slider.addEventListener('input', function() {
					usersInput.value = this.value;
					sliderValue.textContent = this.value;
					updateSupportOptions();
				});

				// Update slider when input changes (for mobile)
				usersInput.addEventListener('input', function() {
					if (slider && this.value >= 1 && this.value <= 50) {
						slider.value = this.value;
						sliderValue.textContent = this.value;
					}
					updateSupportOptions();
				});
			}

			// Tier selection visual feedback
			const tierOptions = form.querySelectorAll('.nova-tier-option');
			const supportOptions = form.querySelectorAll('.nova-support-option');

			// Set initial selected state based on checked radio
			const checkedTier = form.querySelector('input[name="tier"]:checked');
			if (checkedTier) {
				const selectedOption = checkedTier.closest('.nova-tier-option');
				if (selectedOption) {
					selectedOption.classList.add('selected');
				}
			}

			// Handle tier selection clicks
			tierOptions.forEach(option => {
				option.addEventListener('click', function() {
					tierOptions.forEach(opt => opt.classList.remove('selected'));
					this.classList.add('selected');
					this.querySelector('input[type="radio"]').checked = true;
					updateSupportOptions();
					updatePricingSummary();
				});
			});

			// Handle support selection clicks
			supportOptions.forEach(option => {
				option.addEventListener('click', function() {
					if (this.style.display === 'none' || this.classList.contains('disabled')) {
						return;
					}
					supportOptions.forEach(opt => opt.classList.remove('selected'));
					this.classList.add('selected');
					this.querySelector('input[type="radio"]').checked = true;
					updatePricingSummary();
				});
			});

			// Update pricing summary
			function updatePricingSummary() {
				const tier = form.querySelector('input[name="tier"]:checked')?.value;
				const supportLevel = form.querySelector('input[name="support_level"]:checked')?.value || 'self-service';
				const users = parseInt(form.querySelector('[name="users"]').value) || 1;

				// Get billing period
				let billingPeriod = '';
				const billingRadio = form.querySelector('input[name="billing_period"]:checked');
				const billingSelect = form.querySelector('select[name="billing_period"]');
				if (billingRadio) {
					billingPeriod = billingRadio.value;
				} else if (billingSelect) {
					billingPeriod = billingSelect.value;
				}

				// Get product pricing based on billing period
				const tierOption = form.querySelector('[data-tier="' + tier + '"]');
				const countryCode = country === 'au' ? 'Au' : 'Nz';
				const periodSuffix = billingPeriod === 'quarterly' ? 'Quarterly' : 'Annual';
				const productMonthlyCostKey = 'monthlyCost' + countryCode + periodSuffix;
				const productMonthlyCost = tierOption ? parseFloat(tierOption.dataset[productMonthlyCostKey]) || 0 : 0;
				const productTotal = productMonthlyCost * users;

				// Get support pricing (support doesn't vary by billing period)
				const supportOption = form.querySelector('[data-support="' + supportLevel + '"]');
				const supportMonthlyCostKey = 'monthlyCost' + countryCode;
				const supportMonthlyCost = supportOption ? parseFloat(supportOption.dataset[supportMonthlyCostKey]) || 0 : 0;
				const supportIsPerUser = supportOption ? supportOption.dataset.perUser === 'true' : false;
				const supportTotal = supportIsPerUser ? (supportMonthlyCost * users) : supportMonthlyCost;

				// Calculate total
				const totalPerMonth = productTotal + supportTotal;

				// Update display
				const totalAmountEl = document.getElementById('nova-total-amount');
				const billingTextEl = document.getElementById('nova-billing-text');

				if (totalAmountEl) {
					totalAmountEl.textContent = totalPerMonth.toFixed(2);
				}
				if (billingTextEl && billingPeriod) {
					billingTextEl.textContent = billingPeriod === 'quarterly' ? 'Quarterly' : 'Annually';
				}
			}

			// Update support options based on tier and user count
			function updateSupportOptions() {
				const tier = form.querySelector('input[name="tier"]:checked')?.value;
				const users = parseInt(form.querySelector('[name="users"]').value) || 1;

				supportOptions.forEach(option => {
					const supportLevel = option.dataset.support;
					const isPerUser = option.dataset.perUser === 'true';
					const maxUsers = parseInt(option.dataset.maxUsers) || Infinity;
					const requiredTier = option.dataset.tier;
					const monthlyCostKey = 'monthlyCost' + (country === 'au' ? 'Au' : 'Nz');
					const monthlyCost = parseFloat(option.dataset[monthlyCostKey]) || 0;

					let shouldHide = false;
					let reason = '';

					// Self-service is always available
					if (supportLevel === 'self-service') {
						option.style.display = 'block';
						option.classList.remove('disabled');
						return;
					}

					// Check tier restrictions
					if (requiredTier && tier !== requiredTier) {
						shouldHide = true;
						reason = 'Not available for ' + tier + ' tier';
					}

					// Check user count restrictions for per-user support
					if (isPerUser && users > maxUsers) {
						shouldHide = true;
						reason = 'Not available for more than ' + maxUsers + ' users';
					}

					// Hide phone support for Ultimate tier
					if (tier === 'ultimate' && supportLevel.startsWith('phone-')) {
						shouldHide = true;
						reason = 'Not available for Ultimate tier';
					}

					// Calculate and compare costs for per-user options
					if (isPerUser && !shouldHide) {
						const perUserTotal = monthlyCost * users;

						// Check if trainer is cheaper
						const trainerOption = form.querySelector('[data-support="trainer"]');
						const trainerCost = parseFloat(trainerOption?.dataset[monthlyCostKey]) || 0;

						if (perUserTotal >= trainerCost) {
							shouldHide = true;
							reason = 'Trainer option is more cost-effective';
						}
					}

					if (shouldHide) {
						option.style.display = 'none';
						option.classList.add('disabled');
						// If this option was selected, reset to self-service
						if (option.querySelector('input[type="radio"]').checked) {
							form.querySelector('[data-support="self-service"] input[type="radio"]').checked = true;
							// Manually trigger pricing update since programmatic change doesn't fire event
							updatePricingSummary();
						}
					} else {
						option.style.display = 'block';
						option.classList.remove('disabled');

						// Update pricing display
						const pricingDiv = option.querySelector('.nova-support-pricing');
						if (pricingDiv) {
							if (isPerUser) {
								const total = monthlyCost * users;
								pricingDiv.textContent = '$' + monthlyCost + ' × ' + users + ' users = $' + total + '/month';
							} else {
								pricingDiv.textContent = '$' + monthlyCost + '/month';
							}
						}
					}
				});

				// Update pricing summary after support options update
				updatePricingSummary();
			}

			// Listen for user count changes
			form.querySelector('[name="users"]').addEventListener('input', function() {
				updateSupportOptions();
				updatePricingSummary();
			});

			// Listen for tier changes
			form.querySelectorAll('input[name="tier"]').forEach(radio => {
				radio.addEventListener('change', function() {
					updateSupportOptions();
					updatePricingSummary();
				});
			});

			// Listen for support level changes
			form.querySelectorAll('input[name="support_level"]').forEach(radio => {
				radio.addEventListener('change', function() {
					updatePricingSummary();
				});
			});

			// Listen for billing period changes
			const billingRadios = form.querySelectorAll('input[name="billing_period"]');
			const billingSelect = form.querySelector('select[name="billing_period"]');
			if (billingRadios.length > 0) {
				billingRadios.forEach(radio => {
					radio.addEventListener('change', updatePricingSummary);
				});
			}
			if (billingSelect) {
				billingSelect.addEventListener('change', updatePricingSummary);
			}

			// Initial update
			updateSupportOptions();

			// Form submission
			form.addEventListener('submit', async function(e) {
				e.preventDefault();

				const button = form.querySelector('.nova-submit-button');
				const errorDiv = form.querySelector('.nova-error');
				const originalButtonText = button.textContent;

				// Get form values
				const tier = form.querySelector('input[name="tier"]:checked')?.value || form.querySelector('input[name="tier"]')?.value;
				const supportLevel = form.querySelector('input[name="support_level"]:checked')?.value || 'self-service';

				// Get billing period from either toggle switch or dropdown
				let billingPeriod = '';
				const billingRadio = form.querySelector('input[name="billing_period"]:checked');
				const billingSelect = form.querySelector('select[name="billing_period"]');
				if (billingRadio) {
					billingPeriod = billingRadio.value;
				} else if (billingSelect) {
					billingPeriod = billingSelect.value;
				}

				const users = parseInt(form.querySelector('[name="users"]').value);
				const country = form.dataset.country;
				const successUrl = form.dataset.successUrl;
				const cancelUrl = form.dataset.cancelUrl;

				// Validate
				if (!tier || !billingPeriod || !users || !supportLevel) {
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
							support_level: supportLevel,
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
			width: 100%;
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
			background: #fff;
		}
		.nova-tier-option input[type="radio"] {
			margin-right: 10px;
			vertical-align: top;
			margin-top: 3px;
		}
		.nova-tier-content {
			display: inline-block;
			width: calc(100% - 30px);
		}
		.nova-tier-content strong {
			color: var(--primary);
			transition: color 0.2s;
		}
		.nova-tier-badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: 600;
			margin-left: 10px;
			color: white;
			background: var(--primary);
			transition: all 0.2s;
		}
		.nova-tier-description {
			margin-top: 8px;
			color: #666;
			font-size: 14px;
			line-height: 1.2em;
			transition: color 0.2s;
		}
		/* Selected tier styling */
		.nova-tier-option.selected {
			background: var(--primary);
			border-color: var(--primary);
		}
		.nova-tier-option.selected .nova-tier-content strong {
			color: var(--white);
		}
		.nova-tier-option.selected .nova-tier-badge {
			background: var(--warning);
		}
		.nova-tier-option.selected .nova-tier-description {
			color: var(--white);
		}

		/* Support option styles */
		.nova-support-option {
			display: block;
			padding: 12px;
			border: 2px solid #e0e0e0;
			border-radius: 6px;
			margin-bottom: 8px;
			cursor: pointer;
			transition: all 0.2s;
			background: #fff;
		}
		.nova-support-option.disabled {
			opacity: 0.5;
			cursor: not-allowed;
		}
		.nova-support-option input[type="radio"] {
			margin-right: 10px;
			vertical-align: top;
			margin-top: 3px;
		}
		.nova-support-content {
			display: inline-block;
			width: calc(100% - 30px);
		}
		.nova-support-content strong {
			color: #333;
			font-size: 15px;
		}
		.nova-support-badge {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 10px;
			font-size: 11px;
			font-weight: 600;
			margin-left: 8px;
			color: white;
			background: var(--primary);
			transition: all 0.2s;
		}
		.nova-support-description {
			margin-top: 4px;
			color: #666;
			font-size: 13px;
			line-height: 1.2em;
		}
		.nova-support-pricing {
			margin-top: 4px;
			color: #4CAF50;
			font-size: 13px;
			font-weight: 600;
		}
		.nova-support-option:hover:not(.disabled) {
			border-color: #4CAF50;
			background: #f9f9f9;
		}
		.nova-support-option.selected {
			border-color: #4CAF50;
			background: #e8f5e9;
		}
		.nova-support-option.selected .nova-support-badge {
			background: var(--warning);
		}

		/* Pricing Summary */
		.nova-pricing-summary {
			background: #f5f5f5;
			border: 2px solid #4CAF50;
			border-radius: 8px;
			padding: 16px 20px;
			margin: 20px 0;
			text-align: center;
			order: 10;
		}
		.nova-pricing-total {
			margin-bottom: 4px;
		}
		.nova-pricing-label {
			display: block;
			color: #666;
			font-size: 13px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 4px;
		}
		.nova-pricing-amount {
			display: block;
			color: #4CAF50;
			font-size: 28px;
			font-weight: 700;
			line-height: 1.2;
		}
		.nova-pricing-period {
			color: #666;
			font-size: 14px;
			font-weight: 500;
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

		/* Toggle Switch Styles */
		.switch-wrapper {
			position: relative;
			display: inline-flex;
			padding: 4px;
			border-radius: 30px;
			background: #fff;
			box-shadow: 0 0 5px rgba(0,0,0,0.1);
		}
		.switch-wrapper [type="radio"] {
			position: absolute;
			left: -9999px;
		}
		.switch-wrapper [type="radio"]:checked#nova-quarterly ~ label[for="nova-quarterly"],
		.switch-wrapper [type="radio"]:checked#nova-yearly ~ label[for="nova-yearly"] {
			color: #fff;
		}
		.switch-wrapper [type="radio"]:checked#nova-quarterly ~ label[for="nova-quarterly"]:hover,
		.switch-wrapper [type="radio"]:checked#nova-yearly ~ label[for="nova-yearly"]:hover {
			background: transparent;
		}
		.switch-wrapper [type="radio"]:checked#nova-quarterly + label[for="nova-yearly"] ~ .highlighter {
			transform: none;
		}
		.switch-wrapper [type="radio"]:checked#nova-yearly + label[for="nova-quarterly"] ~ .highlighter {
			transform: translateX(100%);
		}
		.switch-wrapper label {
			font-size: 16px;
			font-weight: 600;
			z-index: 1;
			margin-bottom: 0;
			min-width: 120px;
			line-height: 32px;
			cursor: pointer;
			border-radius: 30px;
			text-align: center;
			text-transform: uppercase;
			transition: all 0.25s ease-in-out;
		}
		.switch-wrapper label:hover {
			color: #4CAF50;
		}
		.switch-wrapper .highlighter {
			position: absolute;
			top: 4px;
			left: 4px;
			width: calc(50% - 4px);
			height: calc(100% - 8px);
			border-radius: 30px;
			background: #4CAF50;
			transition: transform 0.25s ease-in-out;
		}

		/* Range Slider Styles */
		.nova-slider-container {
			display: none;
		}
		.nova-slider-wrapper {
			text-align: center;
		}
		.nova-slider-value {
			font-size: 32px;
			font-weight: 700;
			color: #4CAF50;
			margin-bottom: 10px;
		}
		.nova-slider {
			-webkit-appearance: none;
			appearance: none;
			width: 100%;
			height: 8px;
			border-radius: 5px;
			background: #ddd;
			outline: none;
			margin: 10px 0;
		}
		.nova-slider::-webkit-slider-thumb {
			-webkit-appearance: none;
			appearance: none;
			width: 24px;
			height: 24px;
			border-radius: 50%;
			background: #4CAF50;
			cursor: pointer;
			transition: all 0.2s;
		}
		.nova-slider::-webkit-slider-thumb:hover {
			background: #45a049;
			transform: scale(1.1);
		}
		.nova-slider::-moz-range-thumb {
			width: 24px;
			height: 24px;
			border-radius: 50%;
			background: #4CAF50;
			cursor: pointer;
			border: none;
			transition: all 0.2s;
		}
		.nova-slider::-moz-range-thumb:hover {
			background: #45a049;
			transform: scale(1.1);
		}
		.nova-slider-labels {
			display: flex;
			justify-content: space-between;
			font-size: 12px;
			color: #666;
			margin-top: 5px;
		}

		/* Responsive Layout - Desktop */
		@media (min-width: 768px) {
			/* Use flexbox for form to control order */
			.nova-checkout-form {
				display: flex;
				flex-direction: column;
			}

			/* Center billing period above columns */
			.nova-form-group.nova-billing-period {
				text-align: center;
				margin-bottom: 30px;
				order: 1;
			}

			/* Grid layout: product column, pricing summary, support column */
			.nova-selections-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				grid-template-rows: auto auto;
				gap: 20px;
				margin-bottom: 30px;
				order: 2;
			}

			/* Product selection in first column, first row */
			.nova-selections-grid > div:nth-child(1) {
				grid-column: 1;
				grid-row: 1;
			}

			/* Support selection in second column, spans both rows */
			.nova-selections-grid > div:nth-child(2) {
				grid-column: 2;
				grid-row: 1 / 3;
			}

			/* Pricing summary in first column, second row */
			.nova-pricing-summary {
				grid-column: 1;
				grid-row: 2;
				margin: 0;
			}

			/* Hide regular input, show slider */
			.nova-input-container {
				display: none;
			}
			.nova-slider-container {
				display: block;
				text-align: center;
				margin-bottom: 30px;
				order: 3;
			}

			/* Error and submit button at the end */
			.nova-error {
				order: 4;
			}
			.nova-submit-button {
				order: 5;
			}
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

