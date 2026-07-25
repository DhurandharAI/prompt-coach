<?php
/**
 * Signature verification tool — run from the CLI, no WordPress needed.
 *
 * Two jobs:
 *
 *   1. Self-test the HMAC logic:
 *        php tools/verify-signature.php --self-test
 *
 *   2. Check a real captured webhook, which is how you confirm PayMongo's actual
 *      header format before switching to live keys:
 *        php tools/verify-signature.php --body=payload.json \
 *            --header='t=1753...,te=abc...' --secret=whsk_xxx --mode=test
 *
 * To capture a real one: point a PayMongo test webhook at any request-logging
 * endpoint, trigger a test payment, then save the raw body and the
 * Paymongo-Signature header exactly as received. Byte-for-byte matters — a
 * re-serialised JSON body will never verify.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

if ( PHP_SAPI !== 'cli' ) {
	exit( 'CLI only.' );
}

// Minimal stubs so class-signature.php can be loaded outside WordPress.
define( 'ABSPATH', __DIR__ );

if ( ! class_exists( 'CupcakeLab\Ops\Logger' ) ) {
	// A stand-in that prints instead of writing to the WooCommerce log.
	eval( 'namespace CupcakeLab\Ops; final class Logger {
		public static function info( string $m, array $c = array() ): void { fwrite( STDERR, "  info: $m\n" ); }
		public static function error( string $m, array $c = array() ): void { fwrite( STDERR, "  error: $m\n" ); }
	}' );
}

require_once __DIR__ . '/../includes/class-signature.php';

use CupcakeLab\Ops\Signature;

$options = getopt( '', array( 'self-test', 'body:', 'header:', 'secret:', 'mode:' ) );

if ( isset( $options['self-test'] ) ) {
	exit( self_test() );
}

if ( ! isset( $options['body'], $options['header'], $options['secret'] ) ) {
	fwrite( STDERR, "Usage:\n"
		. "  php tools/verify-signature.php --self-test\n"
		. "  php tools/verify-signature.php --body=FILE --header='...' --secret=whsk_xxx [--mode=test|live]\n" );
	exit( 2 );
}

$body = file_get_contents( (string) $options['body'] );

if ( false === $body ) {
	fwrite( STDERR, "Could not read the body file.\n" );
	exit( 2 );
}

$mode  = (string) ( $options['mode'] ?? 'test' );
$valid = Signature::verify( $body, (string) $options['header'], (string) $options['secret'], $mode );

$parsed = Signature::parse( (string) $options['header'] );

echo 'Header parsed as: ', wordwrap( print_r( array_map(
	static fn( $v ) => is_array( $v ) ? $v : ( is_string( $v ) ? substr( $v, 0, 12 ) . '…' : $v ),
	$parsed
), true ), 100 ), "\n";

if ( $valid ) {
	echo "\n✅ Signature VALID in {$mode} mode. The implementation matches your account.\n";
	exit( 0 );
}

echo "\n❌ Signature did NOT verify.\n\n";
echo "Diagnostics — which candidate payload would have matched:\n";

$candidates = array(
	'timestamp.body' => ( $parsed['timestamp'] ?? '' ) . '.' . $body,
	'body only'      => $body,
);

$given = array_filter( array(
	'te'   => $parsed['test'],
	'li'   => $parsed['live'],
	'bare' => $parsed['bare'][0] ?? null,
) );

foreach ( $candidates as $label => $signed ) {
	$computed = hash_hmac( 'sha256', $signed, (string) $options['secret'] );

	foreach ( $given as $field => $value ) {
		$hit = hash_equals( $computed, (string) $value );
		printf( "  %-16s vs %-5s : %s\n", $label, $field, $hit ? 'MATCH ← use this' : 'no' );
	}
}

echo "\nIf one combination matched, adjust includes/class-signature.php to it.\n";
echo "If none did, the secret is wrong or the body was altered in transit.\n";
exit( 1 );

/**
 * Exercise the verifier against known vectors.
 */
function self_test(): int {
	$secret = 'whsk_test_selftestsecret';
	$body   = '{"data":{"id":"evt_test_123","attributes":{"type":"payment.paid"}}}';
	$now    = 1753000000;
	$failed = 0;

	$check = static function ( string $name, bool $got, bool $want ) use ( &$failed ): void {
		$ok = ( $got === $want );
		printf( "%s %s\n", $ok ? 'PASS' : 'FAIL', $name );
		if ( ! $ok ) {
			++$failed;
		}
	};

	// Composite header, test mode.
	$sig    = hash_hmac( 'sha256', $now . '.' . $body, $secret );
	$header = sprintf( 't=%d,te=%s,li=%s', $now, $sig, str_repeat( '0', 64 ) );
	$check( 'composite header verifies in test mode', Signature::verify( $body, $header, $secret, 'test', $now ), true );
	$check( 'test-mode signature rejected in live mode', Signature::verify( $body, $header, $secret, 'live', $now ), false );

	// Composite header, live mode.
	$header_live = sprintf( 't=%d,te=%s,li=%s', $now, str_repeat( '0', 64 ), $sig );
	$check( 'composite header verifies in live mode', Signature::verify( $body, $header_live, $secret, 'live', $now ), true );

	// Tampered body.
	$check(
		'tampered body is rejected',
		Signature::verify( $body . ' ', $header, $secret, 'test', $now ),
		false
	);

	// Wrong secret.
	$check(
		'wrong secret is rejected',
		Signature::verify( $body, $header, 'whsk_wrong', 'test', $now ),
		false
	);

	// Replay outside the tolerance window.
	$check(
		'stale timestamp is rejected (replay guard)',
		Signature::verify( $body, $header, $secret, 'test', $now + 3600 ),
		false
	);
	$check(
		'timestamp just inside tolerance is accepted',
		Signature::verify( $body, $header, $secret, 'test', $now + 299 ),
		true
	);

	// Bare-signature fallback.
	$bare = hash_hmac( 'sha256', $body, $secret );
	$check( 'bare signature header verifies', Signature::verify( $body, $bare, $secret, 'test', $now ), true );
	$check( 'bare signature with wrong digest rejected', Signature::verify( $body, str_repeat( 'a', 64 ), $secret, 'test', $now ), false );

	// Missing pieces.
	$check( 'empty header rejected', Signature::verify( $body, '', $secret, 'test', $now ), false );
	$check( 'empty secret rejected', Signature::verify( $body, $header, '', 'test', $now ), false );

	echo "\n", 0 === $failed ? "All checks passed.\n" : "{$failed} check(s) failed.\n";

	return 0 === $failed ? 0 : 1;
}
