<?php
/**
 * Persist and surface the custom order fields.
 *
 * Meta keys are prefixed and written through WooCommerce's CRUD so they work with
 * HPOS (High-Performance Order Storage) rather than only the legacy post table.
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Meta keys this theme owns, mapped to their admin labels.
 */
function cupcakelab_meta_labels(): array {
	return array(
		'_cupcakelab_viber'      => __( 'Viber number', 'cupcakelab' ),
		'_cupcakelab_fulfilment' => __( 'Fulfilment', 'cupcakelab' ),
		'_cupcakelab_zone'       => __( 'Delivery area', 'cupcakelab' ),
		'_cupcakelab_branch'     => __( 'Pickup branch', 'cupcakelab' ),
		'_cupcakelab_date'       => __( 'Preferred date', 'cupcakelab' ),
		'_cupcakelab_time'       => __( 'Preferred time', 'cupcakelab' ),
		'_cupcakelab_dedication' => __( 'Dedication', 'cupcakelab' ),
		'_cupcakelab_cci'        => __( 'Corporate / bulk (CCI)', 'cupcakelab' ),
		'_cupcakelab_reference'  => __( 'Design reference', 'cupcakelab' ),
	);
}

/**
 * Normalise a Philippine mobile number to 09XXXXXXXXX.
 *
 * Ops copy this straight out of the Telegram card into Viber, so a single
 * consistent format saves a step and avoids typos.
 */
function cupcakelab_normalise_ph_mobile( string $raw ): string {
	$digits = preg_replace( '/\D+/', '', $raw );

	if ( str_starts_with( $digits, '63' ) && 12 === strlen( $digits ) ) {
		return '0' . substr( $digits, 2 );
	}
	if ( str_starts_with( $digits, '9' ) && 10 === strlen( $digits ) ) {
		return '0' . $digits;
	}

	return $digits;
}

/**
 * Save the extra fields onto the order.
 */
function cupcakelab_save_order_fields( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$posted = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- Woo verified the checkout nonce upstream.

	$simple = array(
		'_cupcakelab_fulfilment' => 'cupcakelab_fulfilment',
		'_cupcakelab_zone'       => 'cupcakelab_zone',
		'_cupcakelab_branch'     => 'cupcakelab_branch',
		'_cupcakelab_time'       => 'cupcakelab_time',
	);

	foreach ( $simple as $meta_key => $post_key ) {
		if ( isset( $posted[ $post_key ] ) ) {
			$order->update_meta_data( $meta_key, sanitize_text_field( (string) $posted[ $post_key ] ) );
		}
	}

	if ( isset( $posted['cupcakelab_viber'] ) ) {
		$order->update_meta_data(
			'_cupcakelab_viber',
			cupcakelab_normalise_ph_mobile( (string) $posted['cupcakelab_viber'] )
		);
	}

	if ( isset( $posted['cupcakelab_date'] ) ) {
		$date = sanitize_text_field( (string) $posted['cupcakelab_date'] );
		// Store as Y-m-d only if it really parses as a date.
		$parsed = DateTimeImmutable::createFromFormat( 'Y-m-d', $date );
		if ( $parsed && $parsed->format( 'Y-m-d' ) === $date ) {
			$order->update_meta_data( '_cupcakelab_date', $date );
		}
	}

	if ( isset( $posted['cupcakelab_dedication'] ) ) {
		$order->update_meta_data(
			'_cupcakelab_dedication',
			sanitize_text_field( mb_substr( (string) $posted['cupcakelab_dedication'], 0, 120 ) )
		);
	}

	$order->update_meta_data( '_cupcakelab_cci', empty( $posted['cupcakelab_cci'] ) ? 'no' : 'yes' );

	$attachment_id = cupcakelab_store_reference_upload( $order_id );
	if ( $attachment_id ) {
		$order->update_meta_data( '_cupcakelab_reference', $attachment_id );
	}

	$order->save();
}
add_action( 'woocommerce_checkout_update_order_meta', 'cupcakelab_save_order_fields', 10, 1 );

/**
 * Move the validated reference image into the media library.
 *
 * Returns the attachment ID, or 0 when there was nothing to store.
 */
function cupcakelab_store_reference_upload( int $order_id ): int {
	if ( empty( $_FILES['cupcakelab_reference']['name'] ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$overrides = array(
		'test_form' => false,
		'mimes'     => array(
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'webp'     => 'image/webp',
		),
	);

	$uploaded = wp_handle_upload( $_FILES['cupcakelab_reference'], $overrides ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated at checkout and re-checked by wp_handle_upload.

	if ( ! is_array( $uploaded ) || isset( $uploaded['error'] ) ) {
		return 0;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $uploaded['type'],
			'post_title'     => sprintf( 'Design reference — order %d', $order_id ),
			'post_status'    => 'inherit',
		),
		$uploaded['file']
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] )
	);

	return (int) $attachment_id;
}

/**
 * Human-readable value for a meta key.
 */
function cupcakelab_display_meta( WC_Order $order, string $meta_key ): string {
	$value = (string) $order->get_meta( $meta_key );

	switch ( $meta_key ) {
		case '_cupcakelab_zone':
			$zones = cupcakelab_delivery_zones();
			return isset( $zones[ $value ] ) ? $zones[ $value ]['label'] : $value;

		case '_cupcakelab_branch':
			$branches = cupcakelab_pickup_branches();
			return $branches[ $value ] ?? $value;

		case '_cupcakelab_date':
			return $value ? wp_date( 'D, j M Y', strtotime( $value ) ) : '';

		case '_cupcakelab_time':
			$windows = array(
				'morning'   => __( 'Morning (9 AM – 12 NN)', 'cupcakelab' ),
				'afternoon' => __( 'Afternoon (12 NN – 3 PM)', 'cupcakelab' ),
				'evening'   => __( 'Late afternoon (3 PM – 6 PM)', 'cupcakelab' ),
			);
			return $windows[ $value ] ?? $value;

		case '_cupcakelab_fulfilment':
			return 'pickup' === $value ? __( 'Pickup', 'cupcakelab' ) : __( 'Delivery', 'cupcakelab' );

		case '_cupcakelab_cci':
			return 'yes' === $value ? __( 'Yes', 'cupcakelab' ) : __( 'No', 'cupcakelab' );

		case '_cupcakelab_reference':
			$url = $value ? wp_get_attachment_url( (int) $value ) : '';
			return $url ? $url : '';
	}

	return $value;
}

/**
 * Show the fields on the admin order screen.
 */
function cupcakelab_admin_order_panel( WC_Order $order ): void {
	echo '<h3>' . esc_html__( 'Cupcake Lab order details', 'cupcakelab' ) . '</h3>';
	echo '<table class="widefat striped" style="margin-bottom:1em">';

	foreach ( cupcakelab_meta_labels() as $meta_key => $label ) {
		$value = cupcakelab_display_meta( $order, $meta_key );
		if ( '' === $value ) {
			continue;
		}

		echo '<tr><th style="width:170px;text-align:left">' . esc_html( $label ) . '</th><td>';

		if ( '_cupcakelab_reference' === $meta_key ) {
			printf(
				'<a href="%1$s" target="_blank" rel="noreferrer noopener"><img src="%1$s" alt="" style="max-width:180px;height:auto;border-radius:6px"></a>',
				esc_url( $value )
			);
		} elseif ( '_cupcakelab_viber' === $meta_key ) {
			// Ops copy this into Viber by hand, so make it easy to grab.
			printf( '<code style="font-size:14px">%s</code>', esc_html( $value ) );
		} else {
			echo esc_html( $value );
		}

		echo '</td></tr>';
	}

	echo '</table>';
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'cupcakelab_admin_order_panel', 10, 1 );

/**
 * Include the details in customer-facing emails and on the thank-you page.
 */
function cupcakelab_email_order_meta( WC_Order $order ): void {
	$rows = array();

	foreach ( cupcakelab_meta_labels() as $meta_key => $label ) {
		if ( in_array( $meta_key, array( '_cupcakelab_cci', '_cupcakelab_reference' ), true ) ) {
			continue;
		}
		$value = cupcakelab_display_meta( $order, $meta_key );
		if ( '' !== $value ) {
			$rows[ $label ] = $value;
		}
	}

	if ( ! $rows ) {
		return;
	}

	echo '<h2>' . esc_html__( 'Your order details', 'cupcakelab' ) . '</h2>';
	echo '<table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #f3d6d6" border="1">';
	foreach ( $rows as $label => $value ) {
		printf(
			'<tr><th scope="row" style="text-align:left">%s</th><td>%s</td></tr>',
			esc_html( $label ),
			esc_html( $value )
		);
	}
	echo '</table>';

	// Sets the expectation the plan's Stage 5 promises, so nobody waits in silence.
	echo '<p style="margin-top:16px">' . esc_html__(
		'Your Cupcake Lab Viber group will be created within business hours — watch for an invite on the Viber number above.',
		'cupcakelab'
	) . '</p>';
}
add_action( 'woocommerce_email_after_order_table', 'cupcakelab_email_order_meta', 10, 1 );

/**
 * Add the delivery fee for the chosen zone.
 *
 * Runs as a fee rather than a shipping method because the rate depends on a
 * checkout field of ours, not on Woo's shipping zones.
 */
function cupcakelab_add_delivery_fee( WC_Cart $cart ): void {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	$posted = array();
	if ( isset( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- reading Woo's own AJAX payload.
		parse_str( wp_unslash( (string) $_POST['post_data'] ), $posted );
	} else {
		$posted = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	$fulfilment = isset( $posted['cupcakelab_fulfilment'] ) ? sanitize_text_field( (string) $posted['cupcakelab_fulfilment'] ) : 'delivery';
	if ( 'delivery' !== $fulfilment ) {
		return;
	}

	$zone_key = isset( $posted['cupcakelab_zone'] ) ? sanitize_text_field( (string) $posted['cupcakelab_zone'] ) : '';
	$zones    = cupcakelab_delivery_zones();

	if ( '' === $zone_key || ! isset( $zones[ $zone_key ] ) ) {
		return;
	}

	$fee = $zones[ $zone_key ]['fee'];

	// A null fee means "we will quote you" -- charge nothing now, and say so.
	if ( null === $fee ) {
		return;
	}

	$cart->add_fee( __( 'Delivery', 'cupcakelab' ), (float) $fee, true );
}
add_action( 'woocommerce_cart_calculate_fees', 'cupcakelab_add_delivery_fee', 10, 1 );

/**
 * Recalculate totals when the fulfilment or zone selection changes.
 */
function cupcakelab_refresh_checkout_on_change(): void {
	if ( ! is_checkout() ) {
		return;
	}
	wp_add_inline_script(
		'cupcakelab',
		"document.addEventListener('change',function(e){"
		. "if(e.target.name==='cupcakelab_zone'||e.target.name==='cupcakelab_fulfilment'){"
		. "if(window.jQuery){window.jQuery(document.body).trigger('update_checkout');}}});"
	);
}
add_action( 'wp_enqueue_scripts', 'cupcakelab_refresh_checkout_on_change', 20 );
