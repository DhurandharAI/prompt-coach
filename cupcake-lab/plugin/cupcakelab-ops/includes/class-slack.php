<?php
/**
 * Slack incoming-webhook transport.
 *
 * Slack holds the approved-designs repository. PLAN.md §5 is honest about the
 * limitation: Slack's API will not give us a public gallery, so designs flow one
 * way (site -> Slack) and approved designs come back via a manual export into the
 * media library.
 *
 * @package cupcakelab-ops
 */

declare( strict_types = 1 );

namespace CupcakeLab\Ops;

defined( 'ABSPATH' ) || exit;

/**
 * Posts to a Slack incoming webhook.
 */
final class Slack {

	/**
	 * Post a Block Kit payload.
	 */
	public function send( array $payload ): bool {
		$url = Config::slack_webhook();

		if ( '' === $url ) {
			Logger::error( 'CUPCAKELAB_SLACK_WEBHOOK_URL is not set; Slack routing is disabled.' );
			return false;
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::error( 'Slack post failed: ' . $response->get_error_message() );
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// Slack answers a successful incoming-webhook post with a literal "ok".
		if ( 200 !== $code ) {
			Logger::error( sprintf(
				'Slack returned HTTP %d: %s',
				$code,
				wp_remote_retrieve_body( $response )
			) );
			return false;
		}

		return true;
	}
}
