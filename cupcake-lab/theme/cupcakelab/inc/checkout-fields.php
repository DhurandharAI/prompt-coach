<?php
/**
 * Checkout fields required by PLAN.md Stage 3.
 *
 * Adds, beyond WooCommerce's defaults:
 *   - Viber number (pre-filled from the billing phone, editable) -- ops needs this
 *     to create the per-order Viber group, since Viber's API cannot do it for us
 *   - Delivery vs pickup, with the fields each one needs
 *   - Fulfilment date, validated against the cart's lead time
 *   - Dedication / topper text
 *   - Reference image upload for custom designs
 *   - Corporate/bulk (CCI) flag, which routes the order to a different channel
 *
 * @package cupcakelab
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Delivery zones and fees.
 *
 * Placeholder rates -- these need confirming against actual Lalamove costs before
 * launch. Anything outside these zones falls through to "quoted after
 * confirmation", which is how the business already handles far deliveries.
 */
function cupcakelab_delivery_zones(): array {
	return (array) apply_filters( 'cupcakelab_delivery_zones', array(
		'qc-near'   => array( 'label' => 'Quezon City (Cubao and nearby)', 'fee' => 150 ),
		'qc-far'    => array( 'label' => 'Quezon City (other areas)',      'fee' => 250 ),
		'manila'    => array( 'label' => 'Manila / San Juan / Mandaluyong', 'fee' => 300 ),
		'makati'    => array( 'label' => 'Makati / BGC / Pasig',            'fee' => 350 ),
		'metro-etc' => array( 'label' => 'Rest of Metro Manila',            'fee' => 450 ),
		'quote'     => array( 'label' => 'Outside Metro Manila — we will quote you', 'fee' => null ),
	) );
}

/**
 * Pickup branches.
 */
function cupcakelab_pickup_branches(): array {
	return (array) apply_filters( 'cupcakelab_pickup_branches', array(
		'cubao' => 'Cubao, Quezon City',
	) );
}

/**
 * Does the cart contain anything needing a design approval step?
 */
function cupcakelab_cart_has_custom_item(): bool {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	foreach ( WC()->cart->get_cart() as $item ) {
		$product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		if ( ! $product_id ) {
			continue;
		}
		$slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
		if ( is_array( $slugs ) && array_intersect( array( 'customize-cakes' ), $slugs ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Field definitions, in render order.
 */
function cupcakelab_order_fields(): array {
	$lead_days = cupcakelab_cart_lead_days();

	$zone_options = array( '' => __( 'Select your area…', 'cupcakelab' ) );
	foreach ( cupcakelab_delivery_zones() as $key => $zone ) {
		$zone_options[ $key ] = null === $zone['fee']
			? $zone['label']
			: sprintf( '%s — %s', $zone['label'], strip_tags( wc_price( $zone['fee'] ) ) );
	}

	$fields = array(
		'cupcakelab_viber' => array(
			'type'        => 'tel',
			'label'       => __( 'Viber number', 'cupcakelab' ),
			'placeholder' => '09XX XXX XXXX',
			'required'    => true,
			'description' => __( 'We create a Viber group for your order — design approvals, updates and delivery photos all happen there.', 'cupcakelab' ),
			'priority'    => 10,
			'class'       => array( 'form-row-wide' ),
		),
		'cupcakelab_fulfilment' => array(
			'type'     => 'radio',
			'label'    => __( 'Delivery or pickup', 'cupcakelab' ),
			'required' => true,
			'options'  => array(
				'delivery' => __( 'Deliver to me', 'cupcakelab' ),
				'pickup'   => __( 'I will pick up', 'cupcakelab' ),
			),
			'default'  => 'delivery',
			'priority' => 20,
			'class'    => array( 'form-row-wide' ),
		),
		'cupcakelab_zone' => array(
			'type'     => 'select',
			'label'    => __( 'Delivery area', 'cupcakelab' ),
			'options'  => $zone_options,
			'required' => false,
			'priority' => 30,
			'class'    => array( 'form-row-wide', 'cupcakelab-if-delivery' ),
		),
		'cupcakelab_branch' => array(
			'type'     => 'select',
			'label'    => __( 'Pickup branch', 'cupcakelab' ),
			'options'  => array( '' => __( 'Select a branch…', 'cupcakelab' ) ) + cupcakelab_pickup_branches(),
			'required' => false,
			'priority' => 40,
			'class'    => array( 'form-row-wide', 'cupcakelab-if-pickup' ),
		),
		'cupcakelab_date' => array(
			'type'              => 'date',
			'label'             => __( 'Preferred date', 'cupcakelab' ),
			'required'          => true,
			'description'       => cupcakelab_lead_time_notice( $lead_days ),
			'priority'          => 50,
			'class'             => array( 'form-row-first' ),
			'custom_attributes' => array(
				'min' => cupcakelab_earliest_fulfilment_date( $lead_days ),
			),
		),
		'cupcakelab_time' => array(
			'type'     => 'select',
			'label'    => __( 'Preferred time', 'cupcakelab' ),
			'required' => true,
			'options'  => array(
				''          => __( 'Select a window…', 'cupcakelab' ),
				'morning'   => __( 'Morning (9 AM – 12 NN)', 'cupcakelab' ),
				'afternoon' => __( 'Afternoon (12 NN – 3 PM)', 'cupcakelab' ),
				'evening'   => __( 'Late afternoon (3 PM – 6 PM)', 'cupcakelab' ),
			),
			'priority' => 60,
			'class'    => array( 'form-row-last' ),
		),
		'cupcakelab_dedication' => array(
			'type'        => 'text',
			'label'       => __( 'Dedication / topper text', 'cupcakelab' ),
			'placeholder' => __( 'e.g. Happy 60th Birthday, Mama!', 'cupcakelab' ),
			'required'    => false,
			'description' => __( 'Exactly as you want it written. Leave blank for no message.', 'cupcakelab' ),
			'maxlength'   => 120,
			'priority'    => 70,
			'class'       => array( 'form-row-wide' ),
		),
		'cupcakelab_cci' => array(
			'type'     => 'checkbox',
			'label'    => __( 'This is a corporate or bulk order', 'cupcakelab' ),
			'required' => false,
			'priority' => 90,
			'class'    => array( 'form-row-wide' ),
		),
	);

	return apply_filters( 'cupcakelab_order_fields', $fields );
}

/**
 * Render the extra fields as their own checkout section.
 */
function cupcakelab_render_order_fields( WC_Checkout $checkout ): void {
	echo '<div id="cupcakelab-order-details" class="cupcakelab-order-details">';
	echo '<h3>' . esc_html__( 'Order details', 'cupcakelab' ) . '</h3>';

	foreach ( cupcakelab_order_fields() as $key => $field ) {
		woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
	}

	if ( cupcakelab_cart_has_custom_item() ) {
		cupcakelab_render_reference_upload();
	}

	echo '</div>';
}
add_action( 'woocommerce_after_order_notes', 'cupcakelab_render_order_fields', 10 );

/**
 * Reference image upload, shown only when the cart has a custom-design item.
 *
 * The file is attached to the order and linked in the Telegram #design card, so
 * production sees the reference without anyone re-sending it over chat.
 */
function cupcakelab_render_reference_upload(): void {
	$types = implode( ',', cupcakelab_allowed_upload_mimes() );
	?>
	<p class="form-row form-row-wide">
		<label for="cupcakelab_reference">
			<?php esc_html_e( 'Design reference image', 'cupcakelab' ); ?>
		</label>
		<span class="woocommerce-input-wrapper">
			<input type="file" id="cupcakelab_reference" name="cupcakelab_reference"
				accept="<?php echo esc_attr( $types ); ?>" />
			<span class="description">
				<?php
				printf(
					/* translators: %s: maximum upload size */
					esc_html__( 'Optional. JPG, PNG or WebP, up to %s. Our design team works from this.', 'cupcakelab' ),
					esc_html( size_format( cupcakelab_max_upload_bytes() ) )
				);
				?>
			</span>
		</span>
	</p>
	<?php
}

/**
 * Allowed reference-image MIME types.
 */
function cupcakelab_allowed_upload_mimes(): array {
	return array( 'image/jpeg', 'image/png', 'image/webp' );
}

/**
 * Upload ceiling for reference images, capped by whatever PHP allows.
 */
function cupcakelab_max_upload_bytes(): int {
	return (int) min( 8 * MB_IN_BYTES, wp_max_upload_size() );
}

/**
 * Validate the extra fields.
 */
function cupcakelab_validate_checkout(): void {
	$posted = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- Woo verifies the checkout nonce before this hook.

	$fulfilment = isset( $posted['cupcakelab_fulfilment'] ) ? sanitize_text_field( (string) $posted['cupcakelab_fulfilment'] ) : '';
	$viber      = isset( $posted['cupcakelab_viber'] ) ? sanitize_text_field( (string) $posted['cupcakelab_viber'] ) : '';
	$date       = isset( $posted['cupcakelab_date'] ) ? sanitize_text_field( (string) $posted['cupcakelab_date'] ) : '';

	// Viber number: PH mobile, accepted as 09XXXXXXXXX or +639XXXXXXXXX.
	$digits = preg_replace( '/\D+/', '', $viber );
	if ( '' === $digits ) {
		wc_add_notice( __( 'Please give us a Viber number so we can set up your order group.', 'cupcakelab' ), 'error' );
	} elseif ( ! preg_match( '/^(?:63|0)?9\d{9}$/', $digits ) ) {
		wc_add_notice(
			__( 'That Viber number does not look like a Philippine mobile number. Please use 09XX XXX XXXX.', 'cupcakelab' ),
			'error'
		);
	}

	if ( 'delivery' === $fulfilment ) {
		if ( empty( $posted['cupcakelab_zone'] ) ) {
			wc_add_notice( __( 'Please choose your delivery area.', 'cupcakelab' ), 'error' );
		}
		if ( empty( $posted['shipping_address_1'] ) && empty( $posted['billing_address_1'] ) ) {
			wc_add_notice( __( 'Please give us a delivery address.', 'cupcakelab' ), 'error' );
		}
	} elseif ( 'pickup' === $fulfilment ) {
		if ( empty( $posted['cupcakelab_branch'] ) ) {
			wc_add_notice( __( 'Please choose a pickup branch.', 'cupcakelab' ), 'error' );
		}
	} else {
		wc_add_notice( __( 'Please tell us whether this is for delivery or pickup.', 'cupcakelab' ), 'error' );
	}

	// The date input has a min attribute, but that is a client-side hint only --
	// the real check has to happen here.
	if ( '' === $date ) {
		wc_add_notice( __( 'Please choose a date for your order.', 'cupcakelab' ), 'error' );
	} else {
		$earliest = cupcakelab_earliest_fulfilment_date();
		if ( $date < $earliest ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: chosen date, 2: earliest allowed date */
					__( 'We need more notice than that. Everything is made to order, so the earliest we can do is %2$s (you chose %1$s).', 'cupcakelab' ),
					esc_html( wp_date( 'j M Y', strtotime( $date ) ) ),
					esc_html( wp_date( 'j M Y', strtotime( $earliest ) ) )
				),
				'error'
			);
		}
	}

	if ( empty( $posted['cupcakelab_time'] ) ) {
		wc_add_notice( __( 'Please choose a preferred time window.', 'cupcakelab' ), 'error' );
	}

	cupcakelab_validate_reference_upload();
}
add_action( 'woocommerce_after_checkout_validation', 'cupcakelab_validate_checkout', 10, 0 );

/**
 * Validate the uploaded reference image before it is ever moved into uploads.
 */
function cupcakelab_validate_reference_upload(): void {
	if ( empty( $_FILES['cupcakelab_reference']['name'] ) ) {
		return;
	}

	$file = $_FILES['cupcakelab_reference']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- inspected below.

	if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
		wc_add_notice( __( 'Your reference image did not upload. Please try again.', 'cupcakelab' ), 'error' );
		return;
	}

	if ( (int) $file['size'] > cupcakelab_max_upload_bytes() ) {
		wc_add_notice(
			sprintf(
				/* translators: %s: maximum size */
				__( 'That reference image is too large. Please keep it under %s.', 'cupcakelab' ),
				size_format( cupcakelab_max_upload_bytes() )
			),
			'error'
		);
		return;
	}

	// Trust the file's actual contents, not its name or the browser's claim.
	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	if ( empty( $check['type'] ) || ! in_array( $check['type'], cupcakelab_allowed_upload_mimes(), true ) ) {
		wc_add_notice( __( 'Reference images must be JPG, PNG or WebP.', 'cupcakelab' ), 'error' );
	}
}

/**
 * Pre-fill the Viber number from the billing phone.
 */
function cupcakelab_default_viber( $value, string $key ) {
	if ( 'cupcakelab_viber' === $key && empty( $value ) ) {
		$phone = WC()->checkout()->get_value( 'billing_phone' );
		if ( $phone ) {
			return $phone;
		}
	}
	return $value;
}
add_filter( 'woocommerce_checkout_get_value', 'cupcakelab_default_viber', 10, 2 );
