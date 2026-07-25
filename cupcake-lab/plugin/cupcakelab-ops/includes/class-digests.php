<?php
/**
 * Scheduled digests, per PLAN.md §5.
 *
 *   Daily 6 AM  -> #production  bake list for today + tomorrow
 *   Daily 6 AM  -> #dispatch    delivery manifest
 *   Daily 8 PM  -> #general     orders count, revenue, tomorrow's load
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Builds and sends the daily digests.
 */
final class Digests {

	public const MORNING_HOOK = 'cupcakelab_ops_morning_digest';
	public const EVENING_HOOK = 'cupcakelab_ops_evening_digest';

	public function __construct( private Telegram $telegram = new Telegram() ) {}

	/**
	 * Hook registration.
	 */
	public function register(): void {
		add_action( self::MORNING_HOOK, array( $this, 'morning' ) );
		add_action( self::EVENING_HOOK, array( $this, 'evening' ) );
	}

	/**
	 * Schedule both digests in the site's timezone.
	 */
	public static function schedule(): void {
		foreach ( array( self::MORNING_HOOK => '06:00', self::EVENING_HOOK => '20:00' ) as $hook => $time ) {
			if ( wp_next_scheduled( $hook ) ) {
				continue;
			}

			// wp_schedule_event wants UTC. Build the local time first, then convert,
			// so a Manila 6 AM stays 6 AM in Manila regardless of server timezone.
			$local = current_datetime()->modify( $time );
			if ( $local->getTimestamp() <= time() ) {
				$local = $local->modify( '+1 day' );
			}

			wp_schedule_event( $local->getTimestamp(), 'daily', $hook );
		}
	}

	/**
	 * Clear both.
	 */
	public static function unschedule(): void {
		foreach ( array( self::MORNING_HOOK, self::EVENING_HOOK ) as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * 6 AM: bake list and dispatch manifest.
	 */
	public function morning(): void {
		$today    = current_datetime()->format( 'Y-m-d' );
		$tomorrow = current_datetime()->modify( '+1 day' )->format( 'Y-m-d' );

		$this->telegram->send( 'production', $this->bake_list( $today, $tomorrow ) );
		$this->telegram->send( 'dispatch', $this->manifest( $today ) );
	}

	/**
	 * 8 PM: the day's numbers and tomorrow's load.
	 */
	public function evening(): void {
		$today    = current_datetime()->format( 'Y-m-d' );
		$tomorrow = current_datetime()->modify( '+1 day' )->format( 'Y-m-d' );

		$orders  = $this->orders_placed_on( $today );
		$revenue = array_sum( array_map( static fn( $o ) => (float) $o->get_total(), $orders ) );
		$due     = $this->orders_due_on( $tomorrow );

		$lines = array(
			sprintf( '🌙 <b>Daily digest — %s</b>', Message_Builder::esc( (string) wp_date( 'D, j M Y' ) ) ),
			'',
			sprintf( 'Orders placed today: <b>%d</b>', count( $orders ) ),
			sprintf( 'Revenue today: <b>%s</b>', Message_Builder::esc( Message_Builder::money( $revenue ) ) ),
			sprintf( 'Due tomorrow: <b>%d</b>', count( $due ) ),
		);

		if ( $due ) {
			$delivery = 0;
			$pickup   = 0;

			foreach ( $due as $order ) {
				$ctx = new Order_Context( $order );
				if ( $ctx->is_pickup() ) {
					++$pickup;
				} else {
					++$delivery;
				}
			}

			$lines[] = sprintf( '  → %d delivery, %d pickup', $delivery, $pickup );
		}

		$this->telegram->send( 'general', implode( "\n", $lines ) );
	}

	/**
	 * Aggregate flavours and quantities across two days.
	 */
	private function bake_list( string $today, string $tomorrow ): string {
		$lines = array( '🧑‍🍳 <b>Bake list</b>', '' );

		foreach ( array( $today => 'TODAY', $tomorrow => 'TOMORROW' ) as $date => $label ) {
			$orders = $this->orders_due_on( $date );

			$lines[] = sprintf(
				'<b>%s — %s</b> (%d %s)',
				$label,
				Message_Builder::esc( (string) wp_date( 'D, j M', (int) strtotime( $date ) ) ),
				count( $orders ),
				1 === count( $orders ) ? 'order' : 'orders'
			);

			if ( ! $orders ) {
				$lines[] = '  <i>nothing due</i>';
				$lines[] = '';
				continue;
			}

			// Aggregate identical product+variation combinations so the kitchen sees
			// "12 × Red Velvet 6 INCH", not twelve separate lines.
			$totals = array();

			foreach ( $orders as $order ) {
				$ctx = new Order_Context( $order );
				foreach ( $ctx->items() as $item ) {
					$key = $item['name'] . ( '' !== $item['variation'] ? ' — ' . $item['variation'] : '' );
					$totals[ $key ] = ( $totals[ $key ] ?? 0 ) + $item['quantity'];
				}
			}

			ksort( $totals );

			foreach ( $totals as $label_text => $quantity ) {
				$lines[] = sprintf( '  • <b>%d</b> × %s', $quantity, Message_Builder::esc( $label_text ) );
			}

			$lines[] = '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Delivery manifest for a date.
	 */
	private function manifest( string $date ): string {
		$orders = $this->orders_due_on( $date );

		$lines = array(
			sprintf( '🚚 <b>Dispatch manifest — %s</b>', Message_Builder::esc( (string) wp_date( 'D, j M Y', (int) strtotime( $date ) ) ) ),
			'',
		);

		$deliveries = array();

		foreach ( $orders as $order ) {
			$ctx = new Order_Context( $order );
			if ( ! $ctx->is_pickup() ) {
				$deliveries[] = $ctx;
			}
		}

		if ( ! $deliveries ) {
			$lines[] = '<i>No deliveries scheduled.</i>';
		}

		foreach ( $deliveries as $ctx ) {
			$lines[] = sprintf( '<b>#%s</b> — %s', Message_Builder::esc( $ctx->number() ), Message_Builder::esc( $ctx->customer_name() ) );
			$lines[] = sprintf( '  🕐 %s', Message_Builder::esc( $ctx->time_label() ) );
			$lines[] = sprintf( '  📍 %s', Message_Builder::esc( $ctx->destination() ) );
			$lines[] = sprintf( '  📱 %s', Message_Builder::esc( $ctx->phone() ) );
			$lines[] = '';
		}

		$pickups = count( $orders ) - count( $deliveries );
		if ( $pickups > 0 ) {
			$lines[] = sprintf( '<i>Plus %d pickup%s at the branch.</i>', $pickups, 1 === $pickups ? '' : 's' );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Paid orders with a fulfilment date of $date.
	 *
	 * @return \WC_Order[]
	 */
	private function orders_due_on( string $date ): array {
		$orders = wc_get_orders( array(
			'limit'        => 200,
			'status'       => array( 'processing', 'on-hold', 'completed' ),
			'meta_key'     => '_cupcakelab_date', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'   => $date,              // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_compare' => '=',
			'orderby'      => 'date',
			'order'        => 'ASC',
		) );

		return array_values( array_filter(
			is_array( $orders ) ? $orders : array(),
			static fn( $o ) => $o instanceof \WC_Order
		) );
	}

	/**
	 * Orders created on $date, regardless of fulfilment date.
	 *
	 * @return \WC_Order[]
	 */
	private function orders_placed_on( string $date ): array {
		$orders = wc_get_orders( array(
			'limit'        => 200,
			'status'       => array( 'processing', 'on-hold', 'completed' ),
			'date_created' => $date,
		) );

		return array_values( array_filter(
			is_array( $orders ) ? $orders : array(),
			static fn( $o ) => $o instanceof \WC_Order
		) );
	}
}
