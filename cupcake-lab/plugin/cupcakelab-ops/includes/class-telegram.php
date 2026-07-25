<?php
/**
 * Telegram Bot API transport.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Posts messages into the MCJC channels.
 */
final class Telegram {

	private const API = 'https://api.telegram.org/bot%s/%s';

	/**
	 * Send a message to a logical channel.
	 *
	 * @param string $channel Logical name, e.g. "orders".
	 * @param string $html    Message body in Telegram HTML.
	 * @return bool True when Telegram accepted it.
	 */
	public function send( string $channel, string $html ): bool {
		$chat_id = Config::telegram_channel( $channel );

		if ( '' === $chat_id ) {
			Logger::error( sprintf( 'No Telegram chat ID configured for channel "%s"; message dropped.', $channel ) );
			return false;
		}

		return $this->call( 'sendMessage', array(
			'chat_id'                  => $chat_id,
			'text'                     => $html,
			'parse_mode'               => 'HTML',
			'disable_web_page_preview' => true,
		) );
	}

	/**
	 * Send a photo with a caption.
	 *
	 * Used for the #photos channel and for design references.
	 */
	public function send_photo( string $channel, string $image_url, string $caption ): bool {
		$chat_id = Config::telegram_channel( $channel );

		if ( '' === $chat_id ) {
			Logger::error( sprintf( 'No Telegram chat ID configured for channel "%s"; photo dropped.', $channel ) );
			return false;
		}

		// Telegram caps captions at 1024 characters and rejects longer ones outright.
		if ( mb_strlen( $caption ) > 1024 ) {
			$caption = mb_substr( $caption, 0, 1021 ) . '…';
		}

		return $this->call( 'sendPhoto', array(
			'chat_id'    => $chat_id,
			'photo'      => $image_url,
			'caption'    => $caption,
			'parse_mode' => 'HTML',
		) );
	}

	/**
	 * Call a Bot API method.
	 */
	private function call( string $method, array $body ): bool {
		$token = Config::telegram_token();

		if ( '' === $token ) {
			Logger::error( 'CUPCAKELAB_TELEGRAM_BOT_TOKEN is not set; Telegram routing is disabled.' );
			return false;
		}

		$response = wp_remote_post(
			sprintf( self::API, $token, $method ),
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::error( sprintf(
				'Telegram %s failed: %s',
				$method,
				$response->get_error_message()
			) );
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			// Telegram puts the actual reason in the body, and it is genuinely
			// useful ("chat not found", "bot was blocked", "can't parse entities").
			$decoded     = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			$description = is_array( $decoded ) && isset( $decoded['description'] )
				? (string) $decoded['description']
				: 'no description';

			Logger::error( sprintf(
				'Telegram %s returned HTTP %d: %s',
				$method,
				$code,
				$description
			) );

			return false;
		}

		return true;
	}
}
