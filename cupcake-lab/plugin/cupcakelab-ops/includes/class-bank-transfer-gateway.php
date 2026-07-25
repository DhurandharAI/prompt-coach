<?php
/**
 * Manual bank transfer.
 *
 * Required, not optional. PLAN.md §4: QR Ph settles over InstaPay and is capped
 * around ₱50,000 per transaction, so bulk and corporate orders cannot go through
 * it at all. This gateway places the order as pending payment, shows transfer
 * instructions, and waits for ops to mark it paid — which is also what corporate
 * clients tend to prefer, so it stays after cards go live.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( \WC_Payment_Gateway::class ) ) {
	return;
}

/**
 * Offline bank transfer gateway.
 */
final class Bank_Transfer_Gateway extends \WC_Payment_Gateway {

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		$this->id                 = 'cupcakelab_bank_transfer';
		$this->method_title       = __( 'Bank transfer (manual)', 'cupcakelab-ops' );
		$this->method_description = __(
			'Customer transfers to your bank account and the team confirms it by hand. Needed for orders above the QRPh per-transaction ceiling, and generally preferred by corporate clients.',
			'cupcakelab-ops'
		);
		$this->has_fields         = false;

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Admin settings.
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'cupcakelab-ops' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable bank transfer', 'cupcakelab-ops' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'cupcakelab-ops' ),
				'type'        => 'text',
				'default'     => __( 'Bank transfer', 'cupcakelab-ops' ),
				'desc_tip'    => true,
				'description' => __( 'What the customer sees at checkout.', 'cupcakelab-ops' ),
			),
			'description' => array(
				'title'   => __( 'Description', 'cupcakelab-ops' ),
				'type'    => 'textarea',
				'default' => __(
					'Transfer the total to our bank account and send us the receipt. We will confirm your order once the transfer lands — usually within a few hours on banking days.',
					'cupcakelab-ops'
				),
			),
			'instructions' => array(
				'title'       => __( 'Transfer instructions', 'cupcakelab-ops' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __(
					'Account name, bank, and account number. Shown on the thank-you page and in the order email. Leave the account number out of any public page.',
					'cupcakelab-ops'
				),
			),
			'min_amount' => array(
				'title'       => __( 'Minimum order total', 'cupcakelab-ops' ),
				'type'        => 'number',
				'default'     => '0',
				'description' => __(
					'Hide this method below the given total. Set it to 50000 to offer bank transfer only where QRPh cannot reach.',
					'cupcakelab-ops'
				),
			),
		);
	}

	/**
	 * Hide the method below the configured minimum.
	 */
	public function is_available(): bool {
		if ( ! parent::is_available() ) {
			return false;
		}

		$minimum = (float) $this->get_option( 'min_amount', '0' );

		if ( $minimum > 0 && WC()->cart instanceof \WC_Cart ) {
			if ( (float) WC()->cart->get_total( 'edit' ) < $minimum ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Place the order as awaiting payment.
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array( 'result' => 'failure' );
		}

		// on-hold, not processing: nothing has been paid yet, and stock should be
		// reserved rather than reduced until the transfer is confirmed.
		$order->update_status(
			'on-hold',
			__( 'Awaiting bank transfer. Mark the order processing once the funds land.', 'cupcakelab-ops' )
		);

		wc_reduce_stock_levels( $order_id );
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Instructions on the thank-you page.
	 */
	public function thankyou_page(): void {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Instructions in the order email.
	 *
	 * @param \WC_Order $order         Order.
	 * @param bool      $sent_to_admin Whether this is the admin copy.
	 * @param bool      $plain_text    Whether the email is plain text.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ): void {
		if ( $sent_to_admin || ! $this->instructions ) {
			return;
		}
		if ( $this->id !== $order->get_payment_method() || ! $order->has_status( 'on-hold' ) ) {
			return;
		}

		echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ) . PHP_EOL;
	}
}
