<?php
/**
 * Theme supports, menus and editor configuration.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports.
 */
function cupcakelab_setup(): void {
	load_theme_textdomain( 'cupcakelab', CUPCAKELAB_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	// WooCommerce. The gallery features are what give the product page its
	// zoom/lightbox/carousel without shipping our own JS.
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 500,
		'single_image_width'    => 1000,
		'product_grid'          => array(
			'default_columns' => 4,
			'min_columns'     => 2,
			'max_columns'     => 5,
		),
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( array(
		'primary' => __( 'Primary menu', 'cupcakelab' ),
		'footer'  => __( 'Footer menu', 'cupcakelab' ),
		'legal'   => __( 'Legal menu (terms, privacy, refunds, delivery)', 'cupcakelab' ),
	) );

	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'cupcakelab_setup' );

/**
 * Content width used by oEmbeds and wide images.
 */
function cupcakelab_content_width(): void {
	$GLOBALS['content_width'] = 1000;
}
add_action( 'after_setup_theme', 'cupcakelab_content_width', 0 );

/**
 * Footer widget areas.
 */
function cupcakelab_widgets_init(): void {
	for ( $i = 1; $i <= 3; $i++ ) {
		register_sidebar( array(
			'name'          => sprintf( __( 'Footer %d', 'cupcakelab' ), $i ),
			'id'            => 'footer-' . $i,
			'before_widget' => '<div class="footer-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4>',
			'after_title'   => '</h4>',
		) );
	}
}
add_action( 'widgets_init', 'cupcakelab_widgets_init' );

/**
 * Trim the default WordPress head output.
 *
 * Shared hosting plus mobile traffic from Instagram means every removed request
 * counts. None of these are used by this theme.
 */
function cupcakelab_clean_head(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'cupcakelab_clean_head' );

/**
 * Drop the block editor's global stylesheet on the front end.
 *
 * The theme styles everything it renders, and this file is ~90KB uncompressed.
 */
function cupcakelab_dequeue_block_library(): void {
	if ( ! is_admin() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'cupcakelab_dequeue_block_library', 100 );
