<?php
/**
 * PayMongo webhook signature verification.
 *
 * ⚠️ VERIFY BEFORE GO-LIVE
 * developers.paymongo.com is blocked by this environment's egress policy, so the
 * exact header grammar below could not be read off the official documentation
 * while writing it. It implements PayMongo's documented scheme as understood:
 *
 *   Header:  Paymongo-Signature: t=<unix_ts>,te=<test_sig>,li=<live_sig>
 *   Signed:  "<unix_ts>.<raw_request_body>"
 *   Algo:    HMAC-SHA256 keyed with the webhook secret (whsk_…)
 *   Compare: te in test mode, li in live mode
 *
 * A simpler variant -- a bare hex signature over the raw body with no timestamp
 * -- is also accepted, because some PayMongo integrations document that form. The
 * parser tells the two apart by looking for the "t=" prefix.
 *
 * Run tools/verify-signature.php against a real captured test webhook to confirm
 * which form your account actually sends before switching to live keys. If
 * neither matches, the mismatch is logged with the header shape (never the
 * secret), which is enough to adjust this class.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies webhook authenticity.
 */
final class Signature {

	/**
	 * Reject anything older than this, to blunt replay attempts.
	 */
	private const TOLERANCE_SECONDS = 300;

	/**
	 * Verify a raw request body against the signature header.
	 *
	 * @param string $raw_body  Untouched request body. Any re-encoding breaks this.
	 * @param string $header    Paymongo-Signature header value.
	 * @param string $secret    Webhook secret key.
	 * @param string $mode      "test" or "live".
	 * @param int    $now       Current unix timestamp, injectable for testing.
	 * @return bool
	 */
	public static function verify(
		string $raw_body,
		string $header,
		string $secret,
		string $mode,
		?int $now = null
	): bool {
		if ( '' === $secret ) {
			Logger::error( 'Webhook rejected: CUPCAKELAB_PAYMONGO_WEBHOOK_SECRET is not set.' );
			return false;
		}

		if ( '' === $header ) {
			Logger::error( 'Webhook rejected: no Paymongo-Signature header.' );
			return false;
		}

		$parsed = self::parse( $header );

		// Composite form: t=…,te=…,li=…
		if ( null !== $parsed['timestamp'] ) {
			$now = $now ?? time();

			if ( abs( $now - $parsed['timestamp'] ) > self::TOLERANCE_SECONDS ) {
				Logger::error( sprintf(
					'Webhook rejected: timestamp %d is outside the %d second tolerance (now %d).',
					$parsed['timestamp'],
					self::TOLERANCE_SECONDS,
					$now
				) );
				return false;
			}

			$expected = $parsed[ 'live' === $mode ? 'live' : 'test' ];

			if ( '' === (string) $expected ) {
				Logger::error( sprintf(
					'Webhook rejected: header carried no %s-mode signature. Present keys: %s',
					$mode,
					implode( ',', $parsed['keys'] )
				) );
				return false;
			}

			$computed = hash_hmac( 'sha256', $parsed['timestamp'] . '.' . $raw_body, $secret );

			if ( hash_equals( $computed, (string) $expected ) ) {
				return true;
			}

			Logger::error( sprintf(
				'Webhook rejected: %s-mode signature mismatch. Header keys: %s. '
				. 'If the payload is genuine, the signed-payload format may differ from '
				. 'the one implemented -- see the note at the top of class-signature.php.',
				$mode,
				implode( ',', $parsed['keys'] )
			) );

			return false;
		}

		// Bare form: a single hex digest over the raw body.
		$computed = hash_hmac( 'sha256', $raw_body, $secret );

		foreach ( $parsed['bare'] as $candidate ) {
			if ( hash_equals( $computed, $candidate ) ) {
				return true;
			}
		}

		Logger::error(
			'Webhook rejected: signature did not match in either the composite or bare form. '
			. 'Header shape: ' . self::describe( $header )
		);

		return false;
	}

	/**
	 * Parse the signature header into its parts.
	 *
	 * @return array{timestamp:?int,test:?string,live:?string,bare:string[],keys:string[]}
	 */
	public static function parse( string $header ): array {
		$out = array(
			'timestamp' => null,
			'test'      => null,
			'live'      => null,
			'bare'      => array(),
			'keys'      => array(),
		);

		foreach ( explode( ',', $header ) as $segment ) {
			$segment = trim( $segment );
			if ( '' === $segment ) {
				continue;
			}

			if ( ! str_contains( $segment, '=' ) ) {
				$out['bare'][] = $segment;
				continue;
			}

			[ $key, $value ] = explode( '=', $segment, 2 );
			$key             = trim( $key );
			$value           = trim( $value );
			$out['keys'][]   = $key;

			switch ( $key ) {
				case 't':
					if ( ctype_digit( $value ) ) {
						$out['timestamp'] = (int) $value;
					}
					break;
				case 'te':
					$out['test'] = $value;
					break;
				case 'li':
					$out['live'] = $value;
					break;
				default:
					// Unknown key. Keep it as a bare candidate so an unexpected
					// name (say "v1=") still has a chance of matching.
					$out['bare'][] = $value;
			}
		}

		return $out;
	}

	/**
	 * Describe a header's shape for logs, without leaking signature material.
	 */
	private static function describe( string $header ): string {
		$parts = array();

		foreach ( explode( ',', $header ) as $segment ) {
			$segment = trim( $segment );
			if ( '' === $segment ) {
				continue;
			}
			if ( str_contains( $segment, '=' ) ) {
				[ $key, $value ] = explode( '=', $segment, 2 );
				$parts[]         = sprintf( '%s=<%d chars>', trim( $key ), strlen( trim( $value ) ) );
			} else {
				$parts[] = sprintf( '<bare %d chars>', strlen( $segment ) );
			}
		}

		return implode( ',', $parts );
	}
}
