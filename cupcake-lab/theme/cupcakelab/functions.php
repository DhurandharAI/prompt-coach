<?php
/**
 * Cupcake Lab theme bootstrap.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

define( 'CUPCAKELAB_VERSION', '1.0.0' );
define( 'CUPCAKELAB_DIR', get_template_directory() );

require_once CUPCAKELAB_DIR . '/inc/setup.php';
require_once CUPCAKELAB_DIR . '/inc/assets.php';
require_once CUPCAKELAB_DIR . '/inc/template-tags.php';
require_once CUPCAKELAB_DIR . '/inc/woocommerce.php';
require_once CUPCAKELAB_DIR . '/inc/lead-time.php';
require_once CUPCAKELAB_DIR . '/inc/checkout-fields.php';
require_once CUPCAKELAB_DIR . '/inc/order-meta.php';
require_once CUPCAKELAB_DIR . '/inc/quick-order.php';
