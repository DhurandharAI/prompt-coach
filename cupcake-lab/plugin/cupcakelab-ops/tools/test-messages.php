<?php
/**
 * Message formatting tests — CLI, no WordPress needed.
 *
 *   php tools/test-messages.php
 *
 * Covers the parts that fail silently in production: Telegram rejects a whole
 * sendMessage call when HTML parse mode hits an unescaped entity, so a customer
 * writing "Mum & Dad" in the dedication field would lose the entire order card
 * from #orders with nothing but a log line to show for it.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

if ( PHP_SAPI !== 'cli' ) {
	exit( 'CLI only.' );
}

define( 'ABSPATH', __DIR__ );

// Message_Builder only needs these two WordPress functions for the paths tested here.
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $url ): string {
		return htmlspecialchars( $url, ENT_QUOTES );
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, $value ) {
		return $value;
	}
}

require_once __DIR__ . '/../includes/class-message-builder.php';

use CupcakeLab\Ops\Message_Builder;

$failed = 0;

/**
 * Assert two values are identical.
 */
function check( string $name, $got, $want ): void {
	global $failed;

	if ( $got === $want ) {
		echo "PASS {$name}\n";
		return;
	}

	++$failed;
	echo "FAIL {$name}\n";
	echo "  expected: " . var_export( $want, true ) . "\n";
	echo "  got     : " . var_export( $got, true ) . "\n";
}

// -- escaping -----------------------------------------------------------------

check(
	'ampersand escaped',
	Message_Builder::esc( 'Mum & Dad' ),
	'Mum &amp; Dad'
);

check(
	'angle brackets escaped',
	Message_Builder::esc( '<b>not markup</b>' ),
	'&lt;b&gt;not markup&lt;/b&gt;'
);

check(
	'injection attempt is neutralised',
	Message_Builder::esc( '</pre><a href="http://evil.test">tap</a>' ),
	'&lt;/pre&gt;&lt;a href="http://evil.test"&gt;tap&lt;/a&gt;'
);

check(
	'quotes are left alone (valid in Telegram HTML text)',
	Message_Builder::esc( "Ana's 60th" ),
	"Ana's 60th"
);

check(
	'emoji and non-ASCII survive intact',
	Message_Builder::esc( 'Ube Halaya 🧁 ñ' ),
	'Ube Halaya 🧁 ñ'
);

check(
	'already-escaped text is escaped again, not double-decoded',
	Message_Builder::esc( '&amp;' ),
	'&amp;amp;'
);

check(
	'empty string is safe',
	Message_Builder::esc( '' ),
	''
);

// -- money --------------------------------------------------------------------

check( 'whole peso amount', Message_Builder::money( 1500.0 ), '₱1,500.00' );
check( 'centavos preserved', Message_Builder::money( 1234.56 ), '₱1,234.56' );
check( 'zero', Message_Builder::money( 0.0 ), '₱0.00' );
check( 'large amount groups thousands', Message_Builder::money( 1234567.89 ), '₱1,234,567.89' );
check( 'rounds to two places', Message_Builder::money( 99.999 ), '₱100.00' );

// -- a realistic hostile dedication ------------------------------------------

$nasty    = 'Happy 60th, Mum & Dad <3 — "best" 100% ';
$escaped  = Message_Builder::esc( $nasty );

check(
	'hostile dedication leaves no raw < or > or bare &',
	(bool) preg_match( '/<|>|&(?!amp;|lt;|gt;)/', $escaped ),
	false
);

echo "\n", 0 === $failed ? "All checks passed.\n" : "{$failed} check(s) failed.\n";
exit( 0 === $failed ? 0 : 1 );
