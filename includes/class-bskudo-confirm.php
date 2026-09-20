<?php
/**
 * Bestätigungs-Tokens vor dem eigentlichen Kartenversand (Double-Opt-In Absender).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Speichert validierte Versanddaten kurzzeitig bis der Absender den Link in der Mail klickt.
 */
class BSKudo_Confirm {

	const TRANSIENT_PREFIX = 'bskudo_confirm_';

	const TTL_SECONDS = 1800;

	const AJAX_ACTION = 'bskudo_confirm_send';

	/**
	 * Token erzeugen und Versanddaten speichern.
	 *
	 * @param array<string, mixed> $data Validierte Daten aus BSKudo_Security.
	 * @return string|false Token oder false.
	 */
	public static function create( $data ) {
		if ( ! is_array( $data ) || empty( $data['sender_email'] ) ) {
			return false;
		}

		$token = self::generate_token();

		if ( '' === $token ) {
			return false;
		}

		$payload = array(
			'card_id'         => isset( $data['card_id'] ) ? absint( $data['card_id'] ) : 0,
			'message'         => isset( $data['message'] ) ? (string) $data['message'] : '',
			'sender_name'     => isset( $data['sender_name'] ) ? sanitize_text_field( (string) $data['sender_name'] ) : '',
			'sender_email'    => sanitize_email( (string) $data['sender_email'] ),
			'recipient_name'  => isset( $data['recipient_name'] ) ? sanitize_text_field( (string) $data['recipient_name'] ) : '',
			'recipient_email' => isset( $data['recipient_email'] ) ? sanitize_email( (string) $data['recipient_email'] ) : '',
			'send_to_self'    => ! empty( $data['send_to_self'] ),
			'send_at'         => isset( $data['send_at'] ) ? absint( $data['send_at'] ) : 0,
			'created'         => time(),
		);

		if ( $payload['card_id'] < 1 || '' === $payload['message'] || ! is_email( $payload['sender_email'] ) ) {
			return false;
		}

		$stored = set_transient( self::TRANSIENT_PREFIX . $token, $payload, self::TTL_SECONDS );

		return $stored ? $token : false;
	}

	/**
	 * Token auflösen ohne zu löschen.
	 *
	 * @param string $token Roher Token.
	 * @return array<string, mixed>|null
	 */
	public static function resolve( $token ) {
		$token = self::sanitize_token( $token );

		if ( '' === $token ) {
			return null;
		}

		$payload = get_transient( self::TRANSIENT_PREFIX . $token );

		return is_array( $payload ) ? self::normalize_payload( $payload ) : null;
	}

	/**
	 * Token auflösen und Transient löschen (Einmalverwendung).
	 *
	 * @param string $token Roher Token.
	 * @return array<string, mixed>|null
	 */
	public static function consume( $token ) {
		$token = self::sanitize_token( $token );

		if ( '' === $token ) {
			return null;
		}

		$key     = self::TRANSIENT_PREFIX . $token;
		$payload = get_transient( $key );

		delete_transient( $key );

		return is_array( $payload ) ? self::normalize_payload( $payload ) : null;
	}

	/**
	 * Öffentliche Bestätigungs-URL (admin-ajax, per GET aus E-Mail).
	 *
	 * @param string $token Token.
	 * @return string
	 */
	public static function get_url( $token ) {
		$token = self::sanitize_token( $token );

		if ( '' === $token ) {
			return '';
		}

		return add_query_arg(
			array(
				'action' => self::AJAX_ACTION,
				'token'  => $token,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Token bereinigen.
	 *
	 * @param string $token Roher Token.
	 * @return string
	 */
	public static function sanitize_token( $token ) {
		return preg_replace( '/[^a-zA-Z0-9]/', '', (string) $token );
	}

	/**
	 * Payload für Versand normalisieren.
	 *
	 * @param array<string, mixed> $payload Gespeicherte Daten.
	 * @return array<string, mixed>|null
	 */
	private static function normalize_payload( $payload ) {
		$card_id = isset( $payload['card_id'] ) ? absint( $payload['card_id'] ) : 0;
		$message = isset( $payload['message'] ) ? trim( (string) $payload['message'] ) : '';

		if ( $card_id < 1 || '' === $message ) {
			return null;
		}

		if ( 'kudo_card' !== get_post_type( $card_id ) || 'publish' !== get_post_status( $card_id ) ) {
			return null;
		}

		return array(
			'card_id'         => $card_id,
			'message'         => $message,
			'sender_name'     => isset( $payload['sender_name'] ) ? sanitize_text_field( (string) $payload['sender_name'] ) : '',
			'sender_email'    => sanitize_email( (string) ( $payload['sender_email'] ?? '' ) ),
			'recipient_name'  => isset( $payload['recipient_name'] ) ? sanitize_text_field( (string) $payload['recipient_name'] ) : '',
			'recipient_email' => sanitize_email( (string) ( $payload['recipient_email'] ?? '' ) ),
			'send_to_self'    => ! empty( $payload['send_to_self'] ),
			'send_at'         => isset( $payload['send_at'] ) ? absint( $payload['send_at'] ) : 0,
		);
	}

	/**
	 * Zufälligen Token erzeugen.
	 *
	 * @return string
	 */
	private static function generate_token() {
		try {
			return bin2hex( random_bytes( 16 ) );
		} catch ( Exception $e ) {
			return wp_generate_password( 32, false, false );
		}
	}
}
