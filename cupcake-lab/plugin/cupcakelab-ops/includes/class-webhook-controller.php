<?php
/**
 * PayMongo webhook endpoint.
 *
 *   POST /wp-json/cupcakelab/v1/paymongo
 *
 * Handles payment.paid and payment.failed, per PLAN.md §4.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller.
 */
final class Webhook_Controller {

	/**
	 * How long a processed event ID is remembered, for idempotency.
	 */
	private const DEDUPE_TTL = DAY_IN_SECONDS;

	public function __construct( private Router $router = new Router() ) {}

	/**
	 * Hook registration.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		// Also route orders that reach "processing" or "completed" without a webhook
		// -- a manual bank transfer marked paid by hand, or an admin correcting an
		// order. The router's own guard stops this double-posting alongside a webhook.
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_order_paid' ), 20, 2 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_paid' ), 20, 2 );
	}

	/**
	 * Register the route.
	 */
	public function register_routes(): void {
		register_rest_route(
			'cupcakelab/v1',
			'/paymongo',
			array(
				'methods'  => 'POST',
				// PayMongo cannot present a WordPress credential. Authenticity comes
				// from the HMAC signature, checked before anything else happens.
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'handle' ),
			)
		);
	}

	/**
	 * Handle an incoming webhook.
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		// The signature covers the exact bytes PayMongo sent. Using the parsed
		// params here instead would re-encode the JSON and never match.
		$raw    = $request->get_body();
		$header = (string) $request->get_header( 'paymongo_signature' );

		if ( ! Signature::verify( $raw, $header, Config::webhook_secret(), Config::mode() ) ) {
			// 401 rather than 400: this is an authenticity failure, and PayMongo's
			// retry behaviour should not be encouraged for a bad signature.
			return new \WP_REST_Response( array( 'error' => 'invalid signature' ), 401 );
		}

		$payload = json_decode( $raw, true );

		if ( ! is_array( $payload ) ) {
			Logger::error( 'Webhook body was not valid JSON.' );
			return new \WP_REST_Response( array( 'error' => 'malformed payload' ), 400 );
		}

		$event_id = (string) ( $payload['data']['id'] ?? '' );
		$type     = (string) ( $payload['data']['attributes']['type'] ?? '' );
		$resource = (array) ( $payload['data']['attributes']['data'] ?? array() );
		$attrs    = (array) ( $resource['attributes'] ?? array() );

		if ( '' === $type ) {
			Logger::error( 'Webhook had no event type.', array( 'event_id' => $event_id ) );
			return new \WP_REST_Response( array( 'error' => 'missing event type' ), 400 );
		}

		// Idempotency. PayMongo retries on non-2xx, and a retry must not re-post.
		if ( '' !== $event_id ) {
			$seen_key = 'cupcakelab_evt_' . md5( $event_id );

			if ( get_transient( $seen_key ) ) {
				Logger::info( sprintf( 'Duplicate webhook %s (%s) ignored.', $event_id, $type ) );
				return new \WP_REST_Response( array( 'ok' => true, 'duplicate' => true ), 200 );
			}

			set_transient( $seen_key, 1, self::DEDUPE_TTL );
		}

		Logger::info( sprintf( 'Webhook %s received.', $type ), array( 'event_id' => $event_id ) );

		$order = $this->resolve_order( $attrs, $resource );

		if ( ! $order ) {
			// 200 on purpose. There is nothing to retry -- the order genuinely is not
			// here -- and returning an error would make PayMongo retry for hours.
			Logger::error(
				'Could not match a webhook to an order.',
				array( 'event_id' => $event_id, 'type' => $type )
			);

			return new \WP_REST_Response( array( 'ok' => true, 'matched' => false ), 200 );
		}

		switch ( $type ) {
			case 'payment.paid':
			case 'link.payment.paid':
				$this->mark_paid( $order, $attrs );
				break;

			case 'payment.failed':
				$reason = (string) ( $attrs['last_payment_error'] ?? $attrs['failed_message'] ?? '' );
				$order->add_order_note( sprintf(
					/* translators: %s: failure reason */
					__( 'PayMongo reported the payment failed. %s', 'cupcakelab-ops' ),
					$reason
				) );
				$this->router->payment_failed( $order, $reason );
				break;

			default:
				Logger::info( sprintf( 'Webhook type %s not handled.', $type ) );
		}

		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Mark an order paid and fan out the notifications.
	 */
	private function mark_paid( \WC_Order $order, array $attrs ): void {
		$payment_id = (string) ( $attrs['id'] ?? '' );

		if ( ! $order->is_paid() ) {
			// payment_complete() moves the order to processing, decrements stock and
			// fires Woo's own paid hooks.
			$order->payment_complete( $payment_id );
		}

		$ctx         = new Order_Context( $order );
		$financials  = $this->router->financials( $ctx, $attrs );

		$order->add_order_note( sprintf(
			/* translators: 1: gross, 2: fee, 3: net, 4: estimate marker */
			__( 'PayMongo payment confirmed. Gross %1$s, fee %2$s, net %3$s.%4$s', 'cupcakelab-ops' ),
			Message_Builder::money( $financials['gross'] ),
			Message_Builder::money( $financials['fee'] ),
			Message_Builder::money( $financials['net'] ),
			$financials['estimated'] ? ' ' . __( '(fee estimated from configured rate)', 'cupcakelab-ops' ) : ''
		) );

		$order->save();

		$this->router->payment_confirmed( $order );
	}

	/**
	 * Find the WooCommerce order a PayMongo payment belongs to.
	 *
	 * Tries, in order: our own order key in the metadata the checkout sends,
	 * an explicit order id in metadata, then the external reference number.
	 */
	private function resolve_order( array $attrs, array $resource ): ?\WC_Order {
		$metadata = (array) ( $attrs['metadata'] ?? array() );

		// The PayMongo WooCommerce plugin passes the order key through metadata.
		foreach ( array( 'order_key', 'wc_order_key' ) as $key ) {
			if ( ! empty( $metadata[ $key ] ) ) {
				$order_id = wc_get_order_id_by_order_key( (string) $metadata[ $key ] );
				if ( $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order instanceof \WC_Order ) {
						return $order;
					}
				}
			}
		}

		foreach ( array( 'order_id', 'wc_order_id', 'reference_number' ) as $key ) {
			if ( empty( $metadata[ $key ] ) ) {
				continue;
			}
			$order = wc_get_order( (int) $metadata[ $key ] );
			if ( $order instanceof \WC_Order ) {
				return $order;
			}
		}

		// Fall back to the payment's own reference, which the plugin sets to the
		// order number on some flows.
		$reference = (string) ( $attrs['external_reference_number'] ?? $resource['id'] ?? '' );

		if ( '' !== $reference ) {
			$order = wc_get_order( (int) preg_replace( '/\D+/', '', $reference ) );
			if ( $order instanceof \WC_Order ) {
				return $order;
			}
		}

		return null;
	}

	/**
	 * Route orders that become paid without a webhook.
	 *
	 * @param int       $order_id Order ID.
	 * @param \WC_Order $order    Order object.
	 */
	public function on_order_paid( int $order_id, $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( $order instanceof \WC_Order ) {
			$this->router->payment_confirmed( $order );
		}
	}
}
