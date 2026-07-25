<?php
/**
 * Quick Order — the mockup's add-to-cart-without-leaving-the-grid flow.
 *
 * This is the shortest path from an Instagram tap to a cart, which is the whole
 * point of the site per PLAN.md Stage 1-3. A REST route returns a product's
 * purchasable variations, and a second adds one to the cart.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the Quick Order routes.
 */
function cupcakelab_register_routes(): void {
	register_rest_route(
		'cupcakelab/v1',
		'/product/(?P<id>\d+)/options',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true', // Public catalogue data.
			'callback'            => 'cupcakelab_rest_product_options',
			'args'                => array(
				'id' => array(
					'validate_callback' => static fn( $v ): bool => is_numeric( $v ) && (int) $v > 0,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'cupcakelab/v1',
		'/cart/add',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => '__return_true', // Guests must be able to buy.
			'callback'            => 'cupcakelab_rest_add_to_cart',
			'args'                => array(
				'product_id'   => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				'variation_id' => array( 'required' => false, 'sanitize_callback' => 'absint' ),
				'quantity'     => array( 'required' => false, 'sanitize_callback' => 'absint' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'cupcakelab_register_routes' );

/**
 * Return a product's purchasable options.
 */
function cupcakelab_rest_product_options( WP_REST_Request $request ) {
	$product = wc_get_product( (int) $request['id'] );

	if ( ! $product || 'publish' !== $product->get_status() ) {
		return new WP_Error( 'cupcakelab_not_found', __( 'Product not found.', 'cupcakelab' ), array( 'status' => 404 ) );
	}

	$payload = array(
		'id'        => $product->get_id(),
		'name'      => $product->get_name(),
		'permalink' => $product->get_permalink(),
		'leadDays'  => cupcakelab_product_lead_days( $product->get_id() ),
		'options'   => array(),
	);

	if ( $product instanceof WC_Product_Variable ) {
		foreach ( $product->get_available_variations() as $variation ) {
			$child = wc_get_product( $variation['variation_id'] );
			if ( ! $child || ! $child->is_purchasable() || ! $child->is_in_stock() ) {
				continue;
			}
			$payload['options'][] = array(
				'variationId' => $child->get_id(),
				'label'       => implode( ' / ', array_filter( array_map( 'wc_clean', $variation['attributes'] ) ) ),
				'priceHtml'   => wp_strip_all_tags( wc_price( wc_get_price_to_display( $child ) ) ),
			);
		}
	} elseif ( $product->is_purchasable() && $product->is_in_stock() ) {
		$payload['options'][] = array(
			'variationId' => 0,
			'label'       => __( 'Standard', 'cupcakelab' ),
			'priceHtml'   => wp_strip_all_tags( wc_price( wc_get_price_to_display( $product ) ) ),
		);
	}

	return rest_ensure_response( $payload );
}

/**
 * Add an item to the cart.
 */
function cupcakelab_rest_add_to_cart( WP_REST_Request $request ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return new WP_Error( 'cupcakelab_no_cart', __( 'Cart unavailable.', 'cupcakelab' ), array( 'status' => 500 ) );
	}

	$product_id   = (int) $request['product_id'];
	$variation_id = (int) ( $request['variation_id'] ?? 0 );
	$quantity     = max( 1, (int) ( $request['quantity'] ?? 1 ) );

	$product = wc_get_product( $variation_id ?: $product_id );
	if ( ! $product || ! $product->is_purchasable() ) {
		return new WP_Error(
			'cupcakelab_not_purchasable',
			__( 'That item cannot be ordered right now.', 'cupcakelab' ),
			array( 'status' => 400 )
		);
	}

	$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );

	if ( ! $added ) {
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();

		return new WP_Error(
			'cupcakelab_add_failed',
			$notices ? wp_strip_all_tags( (string) $notices[0]['notice'] ) : __( 'Could not add that to your cart.', 'cupcakelab' ),
			array( 'status' => 400 )
		);
	}

	return rest_ensure_response( array(
		'ok'        => true,
		'itemCount' => WC()->cart->get_cart_contents_count(),
		'totalHtml' => wp_strip_all_tags( WC()->cart->get_cart_total() ),
		'cartUrl'   => wc_get_cart_url(),
	) );
}

/**
 * Load the cart and session for our REST routes.
 *
 * WooCommerce only boots the cart for normal front-end requests, so a plain REST
 * call would otherwise have no session to add to.
 */
function cupcakelab_init_cart_for_rest(): void {
	if ( ! function_exists( 'WC' ) ) {
		return;
	}

	$route = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( ! str_contains( $route, 'cupcakelab/v1/cart' ) ) {
		return;
	}

	if ( null === WC()->session ) {
		WC()->initialize_session();
	}
	if ( null === WC()->customer ) {
		WC()->customer = new WC_Customer( get_current_user_id(), true );
	}
	if ( null === WC()->cart ) {
		WC()->initialize_cart();
	}
}
add_action( 'rest_api_init', 'cupcakelab_init_cart_for_rest', 5 );

/**
 * Render the Quick Order dialog once per page.
 */
function cupcakelab_render_quick_order_dialog(): void {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}
	?>
	<div class="quick-order" id="cupcakelab-quick-order" role="dialog" aria-modal="true"
		aria-labelledby="cupcakelab-quick-order-title" hidden>
		<div class="quick-order__dialog">
			<button type="button" class="quick-order__close" data-quick-order-close
				aria-label="<?php esc_attr_e( 'Close', 'cupcakelab' ); ?>">&times;</button>
			<p class="eyebrow"><?php esc_html_e( 'Quick order', 'cupcakelab' ); ?></p>
			<h3 class="quick-order__title" id="cupcakelab-quick-order-title"></h3>
			<p class="quick-order__lead"><?php esc_html_e( 'Choose your size', 'cupcakelab' ); ?></p>
			<div class="quick-order__sizes" data-quick-order-sizes></div>
			<button type="button" class="btn btn--block" data-quick-order-submit disabled>
				<?php esc_html_e( 'Add to cart', 'cupcakelab' ); ?>
			</button>
			<p class="quick-order__status" role="status" aria-live="polite" data-quick-order-status></p>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'cupcakelab_render_quick_order_dialog' );
