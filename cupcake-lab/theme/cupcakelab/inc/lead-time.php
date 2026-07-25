<?php
/**
 * Lead-time rules.
 *
 * From the mockup's own ordering terms:
 *   "All items are made-to-order. Please pre-order at least 3-5 days in advance."
 *   Monogram Cupcakes: "Custom box - 7 days lead time"
 *
 * PLAN.md Stage 2 requires these enforced at checkout rather than stated and
 * hoped for. The rule resolves per cart: the longest lead time among the items
 * wins, because the whole order ships together.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const CUPCAKELAB_LEAD_DEFAULT = 3;
const CUPCAKELAB_LEAD_CUSTOM  = 7;

/**
 * Per-product lead time in days.
 *
 * Resolution order: product meta override, then category default, then the
 * site-wide 3 days. Anything in a custom/bespoke category needs the full 7 days
 * because it goes through design approval first.
 */
function cupcakelab_product_lead_days( int $product_id ): int {
	$override = get_post_meta( $product_id, '_cupcakelab_lead_days', true );
	if ( '' !== $override && is_numeric( $override ) ) {
		return max( 0, (int) $override );
	}

	$custom_slugs = array( 'customize-cakes', 'diy-cake-cupcake' );
	$terms        = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );

	if ( is_array( $terms ) && array_intersect( $custom_slugs, $terms ) ) {
		return CUPCAKELAB_LEAD_CUSTOM;
	}

	/**
	 * Filter a product's lead time in days.
	 *
	 * @param int $days       Resolved lead time.
	 * @param int $product_id Product ID.
	 */
	return (int) apply_filters( 'cupcakelab_product_lead_days', CUPCAKELAB_LEAD_DEFAULT, $product_id );
}

/**
 * Longest lead time across everything currently in the cart.
 */
function cupcakelab_cart_lead_days(): int {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return CUPCAKELAB_LEAD_DEFAULT;
	}

	$days = CUPCAKELAB_LEAD_DEFAULT;

	foreach ( WC()->cart->get_cart() as $item ) {
		$product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		if ( $product_id ) {
			$days = max( $days, cupcakelab_product_lead_days( $product_id ) );
		}
	}

	return $days;
}

/**
 * Earliest date the customer may choose, as Y-m-d in the shop's timezone.
 *
 * Cut-off matters on shared hosting where the server clock may not be Manila
 * time: wp_date() respects the site timezone, date() would not.
 */
function cupcakelab_earliest_fulfilment_date( ?int $lead_days = null ): string {
	$lead_days = $lead_days ?? cupcakelab_cart_lead_days();
	$now       = current_datetime();

	return $now->modify( sprintf( '+%d days', $lead_days ) )->format( 'Y-m-d' );
}

/**
 * Human-readable lead-time notice, shown on product pages and at checkout.
 */
function cupcakelab_lead_time_notice( int $days ): string {
	return sprintf(
		/* translators: 1: number of days, 2: earliest date */
		_n(
			'Made to order — please order at least <strong>%1$d day</strong> ahead. Earliest date available: <strong>%2$s</strong>.',
			'Made to order — please order at least <strong>%1$d days</strong> ahead. Earliest date available: <strong>%2$s</strong>.',
			$days,
			'cupcakelab'
		),
		$days,
		wp_date( 'j M Y', strtotime( cupcakelab_earliest_fulfilment_date( $days ) ) )
	);
}

/**
 * Show the lead time on the single product page, above add-to-cart.
 */
function cupcakelab_render_product_lead_time(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$days = cupcakelab_product_lead_days( $product->get_id() );

	printf(
		'<div class="lead-time-notice"><span aria-hidden="true">📦</span><p>%s</p></div>',
		wp_kses( cupcakelab_lead_time_notice( $days ), array( 'strong' => array() ) )
	);
}
add_action( 'woocommerce_before_add_to_cart_form', 'cupcakelab_render_product_lead_time', 15 );
