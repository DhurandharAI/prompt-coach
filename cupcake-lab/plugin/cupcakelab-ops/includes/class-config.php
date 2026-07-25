<?php
/**
 * Configuration.
 *
 * Secrets are read from constants in wp-config.php, never from the options table.
 * On shared hosting a database dump is the likeliest way credentials leak, and
 * options are the first thing in it.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and validates plugin configuration.
 */
final class Config {

	/**
	 * PayMongo webhook secret key (the whsk_… value shown when the webhook is created).
	 */
	public static function webhook_secret(): string {
		return (string) self::constant( 'CUPCAKELAB_PAYMONGO_WEBHOOK_SECRET', '' );
	}

	/**
	 * "test" or "live". Decides which signature field is authoritative.
	 */
	public static function mode(): string {
		$mode = strtolower( (string) self::constant( 'CUPCAKELAB_PAYMONGO_MODE', 'test' ) );

		return in_array( $mode, array( 'test', 'live' ), true ) ? $mode : 'test';
	}

	/**
	 * Telegram bot token from @BotFather.
	 */
	public static function telegram_token(): string {
		return (string) self::constant( 'CUPCAKELAB_TELEGRAM_BOT_TOKEN', '' );
	}

	/**
	 * Telegram channel IDs, keyed by the logical channel names used in PLAN.md §5.
	 *
	 * Expects a JSON object in the constant, for example:
	 *   define( 'CUPCAKELAB_TELEGRAM_CHANNELS', '{"orders":"-1001234567890", ... }' );
	 *
	 * Channel IDs for supergroups are negative and start with -100.
	 */
	public static function telegram_channels(): array {
		$raw = (string) self::constant( 'CUPCAKELAB_TELEGRAM_CHANNELS', '' );
		if ( '' === $raw ) {
			return array();
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			Logger::error( 'CUPCAKELAB_TELEGRAM_CHANNELS is not valid JSON; no Telegram routing will happen.' );
			return array();
		}

		return array_map( 'strval', $decoded );
	}

	/**
	 * Resolve one logical channel to a Telegram chat ID.
	 */
	public static function telegram_channel( string $name ): string {
		$channels = self::telegram_channels();

		return $channels[ $name ] ?? '';
	}

	/**
	 * Slack incoming-webhook URL for the approved-designs channel.
	 */
	public static function slack_webhook(): string {
		return (string) self::constant( 'CUPCAKELAB_SLACK_WEBHOOK_URL', '' );
	}

	/**
	 * Indicative PayMongo fee rates, used only when the payload omits the real fee.
	 *
	 * PLAN.md §4 records these as indicative and says to verify them at
	 * paymongo.com/pricing. Anything derived from them is labelled an estimate in
	 * the #payables message so nobody reconciles against a guess.
	 *
	 * Shape: [ percentage, fixed peso amount ].
	 */
	public static function fee_rates(): array {
		$defaults = array(
			'card'      => array( 3.5, 15.0 ),
			'gcash'     => array( 2.3, 0.0 ),
			'grab_pay'  => array( 2.3, 0.0 ),
			'paymaya'   => array( 2.3, 0.0 ),
			'qrph'      => array( 1.5, 0.0 ),
			'dob'       => array( 1.5, 0.0 ),
			'brankas'   => array( 1.5, 0.0 ),
			'__default' => array( 2.5, 0.0 ),
		);

		return (array) apply_filters( 'cupcakelab_ops_fee_rates', $defaults );
	}

	/**
	 * Is the plugin configured enough to do anything useful?
	 */
	public static function is_configured(): bool {
		return '' !== self::webhook_secret();
	}

	/**
	 * Read a constant with a default.
	 *
	 * @param string $name    Constant name.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	private static function constant( string $name, $default ) {
		return defined( $name ) ? constant( $name ) : $default;
	}
}
