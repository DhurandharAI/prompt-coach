<?php
/**
 * Event routing, implementing the PLAN.md §5 table.
 *
 *   Payment confirmed              -> #orders   (order card + Viber checklist)
 *   Payment confirmed              -> #payables (gross, fee, net, reference)
 *   Order contains a custom design -> #design   (+ Slack approved-designs)
 *   Corporate/bulk flagged CCI     -> #cci_orders (same card, CCI-tagged)
 *   Payment failed                 -> #orders   (short notice)
 *   Finished-product shot uploaded -> #photos
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Fans an order out to the right channels.
 */
final class Router {

	public function __construct(
		private Telegram $telegram = new Telegram(),
		private Slack $slack = new Slack()
	) {}

	/**
	 * Handle a confirmed payment.
	 *
	 * Guarded so a webhook retry, or a webhook arriving alongside Woo's own status
	 * transition, cannot double-post the same card into the channels.
	 */
	public function payment_confirmed( \WC_Order $order ): void {
		if ( 'yes' === $order->get_meta( '_cupcakelab_notified_paid' ) ) {
			Logger::info( sprintf( 'Order %s already routed; skipping.', $order->get_order_number() ) );
			return;
		}

		$ctx = new Order_Context( $order );

		// Mark before sending. A duplicate webhook that arrives while we are still
		// posting is more likely than a send failing, and a missing card gets
		// noticed immediately whereas a double card confuses the Viber SOP.
		$order->update_meta_data( '_cupcakelab_notified_paid', 'yes' );
		$order->save();

		$card = Message_Builder::order_card( $ctx );

		// CCI orders go to their own channel instead of the general orders channel,
		// so corporate work does not get lost in retail volume.
		$primary = $ctx->is_cci() ? 'cci_orders' : 'orders';
		$this->telegram->send( $primary, $card );

		// The welcome message goes as its own message so ops can copy it cleanly
		// without picking it out of the card.
		$this->telegram->send(
			$primary,
			"<b>Viber welcome message — copy from here</b>\n<pre>"
			. Message_Builder::esc( Message_Builder::viber_welcome( $ctx ) )
			. '</pre>'
		);

		$this->telegram->send( 'payables', Message_Builder::payables( $ctx, $this->financials( $ctx ) ) );

		if ( $ctx->has_custom_item() ) {
			$this->telegram->send( 'design', Message_Builder::design_request( $ctx ) );
			$this->slack->send( Message_Builder::slack_design_request( $ctx ) );
		}

		Logger::info( sprintf( 'Routed paid order %s to %s.', $ctx->number(), $primary ) );
	}

	/**
	 * Handle a failed payment.
	 */
	public function payment_failed( \WC_Order $order, string $reason = '' ): void {
		$ctx = new Order_Context( $order );
		$this->telegram->send( 'orders', Message_Builder::payment_failed( $ctx, $reason ) );
	}

	/**
	 * Post a finished-product shot to #photos.
	 */
	public function product_photo( \WC_Order $order, string $image_url ): void {
		$ctx = new Order_Context( $order );

		$caption = sprintf(
			"📸 <b>Order #%s</b> — %s\n%s",
			Message_Builder::esc( $ctx->number() ),
			Message_Builder::esc( $ctx->customer_name() ),
			Message_Builder::esc( $ctx->date_label() )
		);

		$this->telegram->send_photo( 'photos', $image_url, $caption );
	}

	/**
	 * Work out gross, fee and net for the #payables record.
	 *
	 * Prefers the real numbers from the PayMongo payload. Falls back to the
	 * configured indicative rate and marks the result as an estimate, because
	 * PLAN.md §4 flags those rates as unverified and finance should not reconcile
	 * against a guess believing it to be fact.
	 *
	 * @param Order_Context $ctx     Order.
	 * @param array         $payload Optional PayMongo payment attributes.
	 * @return array{gross:float,fee:float,net:float,reference:string,estimated:bool}
	 */
	public function financials( Order_Context $ctx, array $payload = array() ): array {
		$gross = $ctx->total();

		$fee       = null;
		$net       = null;
		$reference = '';

		if ( isset( $payload['fee'] ) && is_numeric( $payload['fee'] ) ) {
			// PayMongo amounts are in centavos.
			$fee = ( (float) $payload['fee'] ) / 100;
		}
		if ( isset( $payload['net_amount'] ) && is_numeric( $payload['net_amount'] ) ) {
			$net = ( (float) $payload['net_amount'] ) / 100;
		}
		if ( isset( $payload['id'] ) && is_string( $payload['id'] ) ) {
			$reference = $payload['id'];
		}
		if ( '' === $reference ) {
			$reference = (string) $ctx->order()->get_transaction_id();
		}

		$estimated = false;

		if ( null === $fee ) {
			$estimated = true;
			$rates     = Config::fee_rates();
			$key       = $this->fee_key( $ctx->payment_method_key(), $payload );
			[ $percent, $fixed ] = $rates[ $key ] ?? $rates['__default'];
			$fee = round( ( $gross * ( (float) $percent / 100 ) ) + (float) $fixed, 2 );
		}

		if ( null === $net ) {
			$net = round( $gross - $fee, 2 );
		}

		return array(
			'gross'     => $gross,
			'fee'       => $fee,
			'net'       => $net,
			'reference' => $reference,
			'estimated' => $estimated,
		);
	}

	/**
	 * Map a payment method to a fee-rate key.
	 */
	private function fee_key( string $method, array $payload ): string {
		// PayMongo reports the instrument it actually used, which is more reliable
		// than the Woo gateway id when one gateway covers several wallets.
		$source = '';
		if ( isset( $payload['source']['type'] ) && is_string( $payload['source']['type'] ) ) {
			$source = strtolower( $payload['source']['type'] );
		}

		$haystack = strtolower( $method . ' ' . $source );

		$map = array(
			'qrph'    => array( 'qrph', 'qr_ph' ),
			'gcash'   => array( 'gcash' ),
			'paymaya' => array( 'paymaya', 'maya' ),
			'grab_pay' => array( 'grab' ),
			'card'    => array( 'card', 'credit', 'visa', 'master' ),
			'dob'     => array( 'dob', 'online_banking', 'bpi', 'unionbank', 'ubp' ),
			'brankas' => array( 'brankas' ),
		);

		foreach ( $map as $key => $needles ) {
			foreach ( $needles as $needle ) {
				if ( str_contains( $haystack, $needle ) ) {
					return $key;
				}
			}
		}

		return '__default';
	}
}
