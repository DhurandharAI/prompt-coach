<?php
/**
 * Flattens a WooCommerce order into everything the ops messages need.
 *
 * Keeping this separate means the message builders never touch Woo APIs, so they
 * can be read (and their output checked) without a live order.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Order data, resolved once.
 */
final class Order_Context {

	public function __construct( private \WC_Order $order ) {}

	public function order(): \WC_Order {
		return $this->order;
	}

	public function number(): string {
		return $this->order->get_order_number();
	}

	public function id(): int {
		return $this->order->get_id();
	}

	public function admin_url(): string {
		return $this->order->get_edit_order_url();
	}

	public function customer_name(): string {
		$name = trim( $this->order->get_formatted_billing_full_name() );

		return '' !== $name ? $name : __( 'Walk-in customer', 'cupcakelab-ops' );
	}

	/**
	 * "Ana R." -- the short form used in the Viber group name.
	 */
	public function short_name(): string {
		$first = trim( (string) $this->order->get_billing_first_name() );
		$last  = trim( (string) $this->order->get_billing_last_name() );

		if ( '' === $first && '' === $last ) {
			return __( 'Customer', 'cupcakelab-ops' );
		}
		if ( '' === $last ) {
			return $first;
		}

		return $first . ' ' . mb_strtoupper( mb_substr( $last, 0, 1 ) ) . '.';
	}

	public function viber(): string {
		$viber = (string) $this->order->get_meta( '_cupcakelab_viber' );

		return '' !== $viber ? $viber : (string) $this->order->get_billing_phone();
	}

	public function phone(): string {
		return (string) $this->order->get_billing_phone();
	}

	public function email(): string {
		return (string) $this->order->get_billing_email();
	}

	public function is_pickup(): bool {
		return 'pickup' === $this->order->get_meta( '_cupcakelab_fulfilment' );
	}

	public function is_cci(): bool {
		return 'yes' === $this->order->get_meta( '_cupcakelab_cci' );
	}

	/**
	 * Fulfilment date as Y-m-d, or an empty string.
	 */
	public function date(): string {
		return (string) $this->order->get_meta( '_cupcakelab_date' );
	}

	/**
	 * Fulfilment date formatted for humans, e.g. "Sat, 2 Aug 2026".
	 */
	public function date_label(): string {
		$date = $this->date();

		return '' !== $date ? (string) wp_date( 'D, j M Y', (int) strtotime( $date ) ) : __( 'not set', 'cupcakelab-ops' );
	}

	/**
	 * Short date for the Viber group name, e.g. "Aug 2".
	 */
	public function date_short(): string {
		$date = $this->date();

		return '' !== $date ? (string) wp_date( 'M j', (int) strtotime( $date ) ) : '';
	}

	public function time_label(): string {
		$windows = array(
			'morning'   => __( 'Morning (9 AM – 12 NN)', 'cupcakelab-ops' ),
			'afternoon' => __( 'Afternoon (12 NN – 3 PM)', 'cupcakelab-ops' ),
			'evening'   => __( 'Late afternoon (3 PM – 6 PM)', 'cupcakelab-ops' ),
		);
		$key     = (string) $this->order->get_meta( '_cupcakelab_time' );

		return $windows[ $key ] ?? $key;
	}

	public function dedication(): string {
		return (string) $this->order->get_meta( '_cupcakelab_dedication' );
	}

	/**
	 * Where the order goes: an address for delivery, a branch for pickup.
	 */
	public function destination(): string {
		if ( $this->is_pickup() ) {
			$branch = (string) $this->order->get_meta( '_cupcakelab_branch' );

			return sprintf(
				/* translators: %s: branch name */
				__( 'PICKUP — %s', 'cupcakelab-ops' ),
				'' !== $branch ? $branch : __( 'branch not set', 'cupcakelab-ops' )
			);
		}

		$address = $this->order->get_formatted_shipping_address();
		if ( ! $address ) {
			$address = $this->order->get_formatted_billing_address();
		}

		return $address ? wp_strip_all_tags( str_replace( '<br/>', ', ', $address ) ) : __( 'no address given', 'cupcakelab-ops' );
	}

	/**
	 * Line items as [ name, quantity, variation, total ].
	 */
	public function items(): array {
		$items = array();

		foreach ( $this->order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$variation = '';
			$product   = $item->get_product();

			if ( $product instanceof \WC_Product_Variation ) {
				$variation = implode( ' / ', array_filter( array_values( $product->get_variation_attributes() ) ) );
			}

			$items[] = array(
				'name'      => $item->get_name(),
				'quantity'  => (int) $item->get_quantity(),
				'variation' => $variation,
				'total'     => (float) $item->get_total(),
			);
		}

		return $items;
	}

	/**
	 * Does the order need a design approval step?
	 */
	public function has_custom_item(): bool {
		foreach ( $this->order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$slugs = wp_get_post_terms( $item->get_product_id(), 'product_cat', array( 'fields' => 'slugs' ) );
			if ( is_array( $slugs ) && array_intersect( array( 'customize-cakes' ), $slugs ) ) {
				return true;
			}
		}

		// A dedication or an uploaded reference also means the design team is involved.
		return '' !== $this->dedication() || '' !== $this->reference_url();
	}

	/**
	 * URL of the uploaded design reference, if any.
	 */
	public function reference_url(): string {
		$attachment_id = (int) $this->order->get_meta( '_cupcakelab_reference' );

		if ( ! $attachment_id ) {
			return '';
		}

		return (string) wp_get_attachment_url( $attachment_id );
	}

	public function total(): float {
		return (float) $this->order->get_total();
	}

	public function currency(): string {
		return $this->order->get_currency();
	}

	public function payment_method(): string {
		$title = $this->order->get_payment_method_title();

		return '' !== $title ? $title : $this->order->get_payment_method();
	}

	public function payment_method_key(): string {
		return (string) $this->order->get_payment_method();
	}

	/**
	 * Viber group name, per PLAN.md §6: "CL #1042 – Ana R. – Aug 2".
	 */
	public function viber_group_name(): string {
		$parts = array( 'CL #' . $this->number(), $this->short_name() );

		$date = $this->date_short();
		if ( '' !== $date ) {
			$parts[] = $date;
		}

		return implode( ' – ', $parts );
	}
}
