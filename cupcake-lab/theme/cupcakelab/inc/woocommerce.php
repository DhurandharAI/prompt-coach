<?php
/**
 * WooCommerce integration.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Declare compatibility with High-Performance Order Storage.
 *
 * Without this WooCommerce refuses to enable HPOS, and every order query stays on
 * the posts table.
 */
function cupcakelab_declare_hpos(): void {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'cupcakelab_declare_hpos' );

/**
 * Replace Woo's default wrappers with ours.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

function cupcakelab_wrapper_start(): void {
	echo '<main id="main" class="site-main container">';
}
add_action( 'woocommerce_before_main_content', 'cupcakelab_wrapper_start', 10 );

function cupcakelab_wrapper_end(): void {
	echo '</main>';
}
add_action( 'woocommerce_after_main_content', 'cupcakelab_wrapper_end', 10 );

/**
 * Drop the default sidebar and breadcrumb; neither is in the mockup.
 */
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

/**
 * Products per page, matching the mockup's dense grid.
 */
function cupcakelab_products_per_page(): int {
	return 24;
}
add_filter( 'loop_shop_per_page', 'cupcakelab_products_per_page', 20 );

/**
 * Peso formatting: no decimals.
 *
 * Every price in the catalogue is a whole peso, and "₱1,500" reads better than
 * "₱1,500.00" on a product card.
 */
function cupcakelab_price_decimals(): int {
	return 0;
}
add_filter( 'wc_get_price_decimals', 'cupcakelab_price_decimals', 20 );

/**
 * Category pills above the shop grid.
 */
function cupcakelab_category_pills(): void {
	if ( ! is_shop() && ! is_product_category() ) {
		return;
	}

	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'orderby'    => 'name',
	) );

	if ( is_wp_error( $terms ) || ! $terms ) {
		return;
	}

	$current = is_product_category() ? get_queried_object_id() : 0;

	echo '<ul class="category-pills">';
	printf(
		'<li class="%s"><a href="%s">%s</a></li>',
		$current ? '' : 'is-active',
		esc_url( (string) get_permalink( wc_get_page_id( 'shop' ) ) ),
		esc_html__( 'All', 'cupcakelab' )
	);

	foreach ( $terms as $term ) {
		printf(
			'<li class="%s"><a href="%s">%s</a></li>',
			$current === $term->term_id ? 'is-active' : '',
			esc_url( (string) get_term_link( $term ) ),
			esc_html( $term->name )
		);
	}
	echo '</ul>';
}
add_action( 'woocommerce_before_shop_loop', 'cupcakelab_category_pills', 5 );

/**
 * Rebuild the product card.
 *
 * The default loop markup is replaced wholesale so the card can carry the Quick
 * Order button and handle the no-photo case, which applies to most of the
 * catalogue at launch.
 */
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

function cupcakelab_card_open(): void {
	echo '<div class="card">';
	cupcakelab_card_media();
	echo '<div class="card__body">';
}
add_action( 'woocommerce_before_shop_loop_item', 'cupcakelab_card_open', 10 );

/**
 * Card image, or a typographic placeholder when there is no photo.
 */
function cupcakelab_card_media(): void {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$link = get_permalink( $product->get_id() );

	if ( $product->get_image_id() ) {
		printf(
			'<a class="card__media" href="%s" tabindex="-1" aria-hidden="true">%s</a>',
			esc_url( (string) $link ),
			$product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ) // phpcs:ignore WordPress.Security.EscapeOutput -- Woo returns escaped markup.
		);
		return;
	}

	printf(
		'<a class="card__media card__media--empty" href="%s" tabindex="-1" aria-hidden="true"><span>%s</span></a>',
		esc_url( (string) $link ),
		esc_html( $product->get_name() )
	);
}

function cupcakelab_card_title(): void {
	global $product;
	printf(
		'<h3 class="card__title"><a href="%s">%s</a></h3>',
		esc_url( (string) get_permalink( $product->get_id() ) ),
		esc_html( $product->get_name() )
	);
}
add_action( 'woocommerce_shop_loop_item_title', 'cupcakelab_card_title', 10 );

function cupcakelab_card_excerpt(): void {
	global $product;
	$text = $product->get_short_description();
	if ( ! $text ) {
		return;
	}
	printf( '<p class="card__desc">%s</p>', esc_html( wp_trim_words( wp_strip_all_tags( $text ), 18 ) ) );
}
add_action( 'woocommerce_after_shop_loop_item_title', 'cupcakelab_card_excerpt', 8 );

function cupcakelab_card_price(): void {
	global $product;
	$price = $product->get_price_html();
	if ( $price ) {
		printf( '<p class="card__price">%s</p>', wp_kses_post( $price ) );
	}
}
add_action( 'woocommerce_after_shop_loop_item_title', 'cupcakelab_card_price', 10 );

function cupcakelab_card_close(): void {
	global $product;

	echo '<div class="card__actions">';

	if ( $product->is_purchasable() && $product->is_in_stock() ) {
		printf(
			'<button type="button" class="btn btn--block" data-quick-order="%d">%s</button>',
			(int) $product->get_id(),
			esc_html__( 'Quick order', 'cupcakelab' )
		);
	} else {
		printf(
			'<a class="btn btn--ghost btn--block" href="%s">%s</a>',
			esc_url( (string) get_permalink( $product->get_id() ) ),
			esc_html__( 'View details', 'cupcakelab' )
		);
	}

	echo '</div></div></div>';
}
add_action( 'woocommerce_after_shop_loop_item', 'cupcakelab_card_close', 20 );

/**
 * Live cart count in the header without a full page load.
 */
function cupcakelab_cart_fragment( array $fragments ): array {
	ob_start();
	cupcakelab_cart_link();
	$fragments['a.header-cart'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'cupcakelab_cart_fragment' );

/**
 * Header cart link.
 */
function cupcakelab_cart_link(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$count = WC()->cart->get_cart_contents_count();
	printf(
		'<a class="header-cart" href="%s"><span>%s</span><span class="header-cart__count">%d</span></a>',
		esc_url( wc_get_cart_url() ),
		esc_html__( 'Cart', 'cupcakelab' ),
		(int) $count
	);
}

/**
 * Tell customers what happens next, on the thank-you page.
 *
 * PLAN.md Stage 5 promises this explicitly so nobody sits wondering whether the
 * order registered.
 */
function cupcakelab_thankyou_notice( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$viber = (string) $order->get_meta( '_cupcakelab_viber' );
	?>
	<div class="lead-time-notice">
		<span aria-hidden="true">💬</span>
		<p>
			<?php if ( $viber ) : ?>
				<?php
				printf(
					/* translators: %s: customer's Viber number */
					esc_html__( 'We will create your Cupcake Lab Viber group within 2 business hours — watch for an invite on %s. Design approvals, updates and delivery photos all happen there.', 'cupcakelab' ),
					'<strong>' . esc_html( $viber ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput
				);
				?>
			<?php else : ?>
				<?php esc_html_e( 'We will be in touch on Viber within 2 business hours.', 'cupcakelab' ); ?>
			<?php endif; ?>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_thankyou', 'cupcakelab_thankyou_notice', 5, 1 );
