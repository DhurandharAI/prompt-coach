<?php
/**
 * Logging.
 *
 * Uses WooCommerce's logger when available so entries show up under
 * WooCommerce > Status > Logs, which is where the ops team will actually look.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Small wrapper around wc_get_logger().
 */
final class Logger {

	private const SOURCE = 'cupcakelab-ops';

	/**
	 * Log an informational message.
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( 'info', $message, $context );
	}

	/**
	 * Log an error.
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( 'error', $message, $context );
	}

	/**
	 * Write to the log.
	 */
	private static function log( string $level, string $message, array $context ): void {
		if ( $context ) {
			$message .= ' ' . wp_json_encode( self::redact( $context ) );
		}

		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->log( $level, $message, array( 'source' => self::SOURCE ) );
			return;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[%s] %s: %s', self::SOURCE, $level, $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Strip anything secret-shaped before it reaches a log file.
	 *
	 * Logs on shared hosting are readable by anyone with FTP, and a leaked bot
	 * token lets a stranger post into the ops channels.
	 */
	private static function redact( array $context ): array {
		$sensitive = array( 'secret', 'token', 'signature', 'key', 'authorization', 'password' );

		foreach ( $context as $key => $value ) {
			$lower = strtolower( (string) $key );

			foreach ( $sensitive as $needle ) {
				if ( str_contains( $lower, $needle ) ) {
					$context[ $key ] = '[redacted]';
					continue 2;
				}
			}

			if ( is_array( $value ) ) {
				$context[ $key ] = self::redact( $value );
			}
		}

		return $context;
	}
}
