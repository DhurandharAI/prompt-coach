<?php
/**
 * Builds the channel messages described in PLAN.md §5.
 *
 * Telegram messages use HTML parse mode. Everything interpolated goes through
 * esc() first -- an unescaped "&" or "<" in a dedication ("Mum & Dad") makes
 * Telegram reject the whole message, which would silently lose an order card.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Message formatting.
 */
final class Message_Builder {

	/**
	 * Escape for Telegram's HTML parse mode.
	 *
	 * Telegram only requires &, < and > escaped.
	 */
	public static function esc( string $text ): string {
		return str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), $text );
	}

	/**
	 * Format pesos without relying on wc_price()'s HTML.
	 */
	public static function money( float $amount ): string {
		return '₱' . number_format( $amount, 2 );
	}

	/**
	 * The #orders card: everything ops needs to make the Viber group.
	 */
	public static function order_card( Order_Context $ctx ): string {
		$lines = array();

		$lines[] = sprintf(
			'🧾 <b>Order #%s — PAID</b>',
			self::esc( $ctx->number() )
		);

		if ( $ctx->is_cci() ) {
			$lines[] = '🏢 <b>CORPORATE / BULK (CCI)</b>';
		}

		$lines[] = '';
		$lines[] = sprintf( '<b>%s</b>', self::esc( $ctx->customer_name() ) );
		$lines[] = sprintf( '📱 %s', self::esc( $ctx->phone() ) );

		// Tap-to-copy: Telegram copies the contents of a <code> span on tap, which
		// is what PLAN.md §5 asks for on the Viber number.
		$lines[] = sprintf(
			'💬 Viber: <code>%s</code> <i>(tap to copy)</i>',
			self::esc( $ctx->viber() )
		);

		if ( '' !== $ctx->email() ) {
			$lines[] = sprintf( '✉️ %s', self::esc( $ctx->email() ) );
		}

		$lines[] = '';
		$lines[] = '<b>Items</b>';

		foreach ( $ctx->items() as $item ) {
			$label = $item['name'];
			if ( '' !== $item['variation'] ) {
				$label .= ' (' . $item['variation'] . ')';
			}
			$lines[] = sprintf(
				'• %d × %s — %s',
				$item['quantity'],
				self::esc( $label ),
				self::esc( self::money( $item['total'] ) )
			);
		}

		if ( '' !== $ctx->dedication() ) {
			$lines[] = '';
			$lines[] = sprintf( '✍️ <b>Dedication:</b> “%s”', self::esc( $ctx->dedication() ) );
		}

		$lines[] = '';
		$lines[] = sprintf(
			'📅 <b>%s</b> — %s',
			self::esc( $ctx->date_label() ),
			self::esc( $ctx->time_label() )
		);
		$lines[] = sprintf( '📍 %s', self::esc( $ctx->destination() ) );

		$lines[] = '';
		$lines[] = sprintf(
			'💰 <b>%s</b> via %s',
			self::esc( self::money( $ctx->total() ) ),
			self::esc( $ctx->payment_method() )
		);

		if ( '' !== $ctx->reference_url() ) {
			$lines[] = sprintf( '🖼 <a href="%s">Design reference</a>', esc_url( $ctx->reference_url() ) );
		}

		// The Viber group has to be made by hand -- Viber's API cannot create
		// groups. PLAN.md §6 makes this an automation-assisted manual step, so the
		// card carries the checklist rather than assuming anyone remembers it.
		$lines[] = '';
		$lines[] = '<b>➡️ Create the Viber group</b>';
		$lines[] = sprintf( '1. Name it: <code>%s</code>', self::esc( $ctx->viber_group_name() ) );
		$lines[] = sprintf( '2. Add customer: <code>%s</code>', self::esc( $ctx->viber() ) );
		$lines[] = '3. Add CS + production lead';
		$lines[] = '4. Paste the welcome message below';
		$lines[] = '<i>Target: within 2 business hours.</i>';

		$lines[] = '';
		$lines[] = sprintf( '<a href="%s">Open in admin</a>', esc_url( $ctx->admin_url() ) );

		return implode( "\n", $lines );
	}

	/**
	 * The canned Viber welcome message, ready to copy out of Telegram.
	 */
	public static function viber_welcome( Order_Context $ctx ): string {
		$lines = array(
			sprintf( 'Hi %s! 💕 This is Cupcake Lab.', $ctx->short_name() ),
			'',
			sprintf( 'This group is for your order #%s:', $ctx->number() ),
		);

		foreach ( $ctx->items() as $item ) {
			$label = $item['name'];
			if ( '' !== $item['variation'] ) {
				$label .= ' (' . $item['variation'] . ')';
			}
			$lines[] = sprintf( '• %d × %s', $item['quantity'], $label );
		}

		if ( '' !== $ctx->dedication() ) {
			$lines[] = sprintf( 'Dedication: "%s"', $ctx->dedication() );
		}

		$lines[] = '';
		$lines[] = $ctx->is_pickup()
			? sprintf( 'Pickup: %s, %s', $ctx->date_label(), $ctx->time_label() )
			: sprintf( 'Delivery: %s, %s', $ctx->date_label(), $ctx->time_label() );
		$lines[] = '';
		$lines[] = 'Here you will get your design approval, updates, and photos on the day. '
			. 'Anything you need, just message us here. Thank you for ordering with us! 🧁';

		return implode( "\n", $lines );
	}

	/**
	 * The #payables record.
	 */
	public static function payables( Order_Context $ctx, array $financials ): string {
		$lines = array(
			sprintf( '💳 <b>Order #%s</b>', self::esc( $ctx->number() ) ),
			sprintf( 'Method: %s', self::esc( $ctx->payment_method() ) ),
			sprintf( 'Gross: <b>%s</b>', self::esc( self::money( $financials['gross'] ) ) ),
		);

		$suffix = $financials['estimated'] ? ' <i>(estimated)</i>' : '';

		$lines[] = sprintf(
			'Fee: %s%s',
			self::esc( self::money( $financials['fee'] ) ),
			$suffix
		);
		$lines[] = sprintf(
			'Net: <b>%s</b>%s',
			self::esc( self::money( $financials['net'] ) ),
			$suffix
		);

		if ( '' !== (string) $financials['reference'] ) {
			$lines[] = sprintf( 'Ref: <code>%s</code>', self::esc( (string) $financials['reference'] ) );
		}

		if ( $financials['estimated'] ) {
			$lines[] = '';
			$lines[] = '<i>PayMongo did not send a fee on this event, so fee and net are '
				. 'calculated from the configured rate. Reconcile against the payout report.</i>';
		}

		return implode( "\n", $lines );
	}

	/**
	 * The #design request.
	 */
	public static function design_request( Order_Context $ctx ): string {
		$lines = array(
			sprintf( '🎨 <b>Design request — order #%s</b>', self::esc( $ctx->number() ) ),
			sprintf( 'For: %s', self::esc( $ctx->customer_name() ) ),
			sprintf( 'Needed by: <b>%s</b>', self::esc( $ctx->date_label() ) ),
			'',
		);

		foreach ( $ctx->items() as $item ) {
			$label = $item['name'];
			if ( '' !== $item['variation'] ) {
				$label .= ' (' . $item['variation'] . ')';
			}
			$lines[] = sprintf( '• %d × %s', $item['quantity'], self::esc( $label ) );
		}

		if ( '' !== $ctx->dedication() ) {
			$lines[] = '';
			$lines[] = sprintf( '✍️ Topper text: “%s”', self::esc( $ctx->dedication() ) );
		}

		if ( '' !== $ctx->reference_url() ) {
			$lines[] = '';
			$lines[] = sprintf( '🖼 <a href="%s">Customer reference image</a>', esc_url( $ctx->reference_url() ) );
		} else {
			$lines[] = '';
			$lines[] = '<i>No reference image uploaded — ask in the Viber group.</i>';
		}

		$lines[] = '';
		$lines[] = sprintf( '<a href="%s">Open in admin</a>', esc_url( $ctx->admin_url() ) );

		return implode( "\n", $lines );
	}

	/**
	 * A short failure notice.
	 */
	public static function payment_failed( Order_Context $ctx, string $reason ): string {
		$lines = array(
			sprintf( '⚠️ <b>Payment failed — order #%s</b>', self::esc( $ctx->number() ) ),
			sprintf( '%s · %s', self::esc( $ctx->customer_name() ), self::esc( $ctx->viber() ) ),
			sprintf( 'Amount: %s via %s', self::esc( self::money( $ctx->total() ) ), self::esc( $ctx->payment_method() ) ),
		);

		if ( '' !== $reason ) {
			$lines[] = sprintf( 'Reason: %s', self::esc( $reason ) );
		}

		$lines[] = '';
		$lines[] = 'The order is still pending. Worth a follow-up if the customer does not retry.';
		$lines[] = sprintf( '<a href="%s">Open in admin</a>', esc_url( $ctx->admin_url() ) );

		return implode( "\n", $lines );
	}

	/**
	 * Slack block payload for the approved-designs channel.
	 */
	public static function slack_design_request( Order_Context $ctx ): array {
		$item_lines = array();

		foreach ( $ctx->items() as $item ) {
			$label = $item['name'];
			if ( '' !== $item['variation'] ) {
				$label .= ' (' . $item['variation'] . ')';
			}
			$item_lines[] = sprintf( '• %d × %s', $item['quantity'], $label );
		}

		$fields = array(
			array( 'type' => 'mrkdwn', 'text' => "*Order*\n#" . $ctx->number() ),
			array( 'type' => 'mrkdwn', 'text' => "*Needed by*\n" . $ctx->date_label() ),
			array( 'type' => 'mrkdwn', 'text' => "*Customer*\n" . $ctx->customer_name() ),
			array( 'type' => 'mrkdwn', 'text' => "*Viber*\n" . $ctx->viber() ),
		);

		$blocks = array(
			array(
				'type' => 'header',
				'text' => array( 'type' => 'plain_text', 'text' => 'New custom design request' ),
			),
			array( 'type' => 'section', 'fields' => $fields ),
			array(
				'type' => 'section',
				'text' => array( 'type' => 'mrkdwn', 'text' => "*Items*\n" . implode( "\n", $item_lines ) ),
			),
		);

		if ( '' !== $ctx->dedication() ) {
			$blocks[] = array(
				'type' => 'section',
				'text' => array( 'type' => 'mrkdwn', 'text' => '*Topper text*: “' . $ctx->dedication() . '”' ),
			);
		}

		if ( '' !== $ctx->reference_url() ) {
			$blocks[] = array(
				'type'      => 'image',
				'image_url' => $ctx->reference_url(),
				'alt_text'  => 'Customer reference image',
			);
		}

		$blocks[] = array(
			'type'     => 'context',
			'elements' => array(
				array( 'type' => 'mrkdwn', 'text' => '<' . $ctx->admin_url() . '|Open the order in WooCommerce>' ),
			),
		);

		return array(
			'text'   => sprintf( 'New custom design request — order #%s', $ctx->number() ),
			'blocks' => $blocks,
		);
	}
}
