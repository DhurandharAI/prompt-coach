<?php
/**
 * Front-end assets.
 *
 * The mockup loaded seven Google Font families across five requests; six of them
 * were Astra/Elementor/WP leftovers. Only the designer's actual pairing is kept
 * here, in one request.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue styles and scripts.
 */
function cupcakelab_assets(): void {
	wp_enqueue_style(
		'cupcakelab-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'cupcakelab',
		get_stylesheet_uri(),
		array( 'cupcakelab-fonts' ),
		CUPCAKELAB_VERSION
	);

	wp_enqueue_script(
		'cupcakelab',
		get_theme_file_uri( '/assets/js/theme.js' ),
		array(),
		CUPCAKELAB_VERSION,
		true
	);

	if ( function_exists( 'is_woocommerce' ) ) {
		wp_localize_script( 'cupcakelab', 'cupcakelabData', array(
			'restUrl' => esc_url_raw( rest_url( 'cupcakelab/v1/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'cartUrl' => wc_get_cart_url(),
			'i18n'    => array(
				'adding'  => __( 'Adding…', 'cupcakelab' ),
				'added'   => __( 'Added to cart.', 'cupcakelab' ),
				'failed'  => __( 'Sorry, that did not work. Please try the product page.', 'cupcakelab' ),
				'pickOne' => __( 'Choose a size to continue.', 'cupcakelab' ),
			),
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'cupcakelab_assets' );

/**
 * Preconnect to the font host so the single font request starts earlier.
 */
function cupcakelab_resource_hints( array $hints, string $relation ): array {
	if ( 'preconnect' === $relation ) {
		$hints[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'cupcakelab_resource_hints', 10, 2 );
