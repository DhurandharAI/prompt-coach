<?php
/**
 * Plugin Name: Cupcake Lab Ops
 * Description: PayMongo webhook handling plus order routing into the MCJC Telegram channels and the Slack approved-designs repository. Also provides the manual bank transfer payment method required above the QRPh ceiling, and the daily production/dispatch/digest crons.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: MCJC Group
 * Text Domain: cupcakelab-ops
 *
 * PLAN.md §5 specifies "a small PHP webhook handler on the same Hostinger
 * account". This is implemented as a plugin rather than a loose PHP file for two
 * reasons: a loose endpoint sitting in the web root has to bootstrap WordPress
 * itself and is directly reachable regardless of what WordPress thinks, whereas a
 * registered REST route inherits WordPress's request handling. It still runs on
 * the same Hostinger account with no external service, which is the point.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

define( 'CUPCAKELAB_OPS_VERSION', '1.0.0' );
define( 'CUPCAKELAB_OPS_DIR', __DIR__ );

require_once __DIR__ . '/includes/class-config.php';
require_once __DIR__ . '/includes/class-logger.php';
require_once __DIR__ . '/includes/class-signature.php';
require_once __DIR__ . '/includes/class-order-context.php';
require_once __DIR__ . '/includes/class-message-builder.php';
require_once __DIR__ . '/includes/class-telegram.php';
require_once __DIR__ . '/includes/class-slack.php';
require_once __DIR__ . '/includes/class-router.php';
require_once __DIR__ . '/includes/class-webhook-controller.php';
require_once __DIR__ . '/includes/class-digests.php';
require_once __DIR__ . '/includes/class-bank-transfer-gateway.php';

use CupcakeLab\Ops\Bank_Transfer_Gateway;
use CupcakeLab\Ops\Digests;
use CupcakeLab\Ops\Webhook_Controller;

/**
 * Boot the plugin.
 */
function cupcakelab_ops_init(): void {
	( new Webhook_Controller() )->register();
	( new Digests() )->register();
}
add_action( 'plugins_loaded', 'cupcakelab_ops_init' );

/**
 * Register the manual bank transfer gateway.
 *
 * QR Ph settles over InstaPay and is capped near ₱50,000 per transaction, so bulk
 * and corporate orders need a path that does not go through it. See PLAN.md §4.
 */
function cupcakelab_ops_gateways( array $gateways ): array {
	$gateways[] = Bank_Transfer_Gateway::class;
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'cupcakelab_ops_gateways' );

/**
 * Schedule the daily crons on activation.
 *
 * WP-Cron only fires on traffic, which is unreliable for a 6 AM bake list. The
 * README explains how to drive these from a real Hostinger cron job instead.
 */
function cupcakelab_ops_activate(): void {
	Digests::schedule();
}
register_activation_hook( __FILE__, 'cupcakelab_ops_activate' );

/**
 * Clear scheduled events on deactivation.
 */
function cupcakelab_ops_deactivate(): void {
	Digests::unschedule();
}
register_deactivation_hook( __FILE__, 'cupcakelab_ops_deactivate' );
