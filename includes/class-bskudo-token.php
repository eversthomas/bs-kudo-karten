<?php
/**
 * Temporäre Token-URLs für die Karten-Webansicht (WP Transients).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Erzeugt und prüft anonyme View-Tokens ohne personenbezogene Daten.
 */
class BSKudo_Token {

	const TRANSIENT_PREFIX = 'bskudo_view_';

	const QUERY_VAR = 'bskudo_kudo';

	const REWRITE_SLUG = 'kudo-karte';

	/**
	 * Token erzeugen und Payload speichern.
	 *
	 * @param int    $card_id     Kudo-Karten-Post-ID.
	 * @param string $message     Karten-Text.
	 * @param string $sender_name Name des Versenders (für Webansicht).
	 * @return string|false Token oder false bei Fehler.
	 */
	public static function create( $card_id, $message, $sender_name = '' ) {
		$card_id = absint( $card_id );
		$message = trim( (string) $message );

		if ( $card_id < 1 || '' === $message ) {
			return false;
		}

		if ( 'kudo_card' !== get_post_type( $card_id ) || 'publish' !== get_post_status( $card_id ) ) {
			return false;
		}

		$token = self::generate_token();

		if ( '' === $token ) {
			return false;
		}

		$payload = array(
			'card_id'     => $card_id,
			'message'     => $message,
			'created'     => time(),
			'sender_name' => sanitize_text_field( (string) $sender_name ),
		);

		$ttl = BSKudo_Settings::get_token_ttl_days() * DAY_IN_SECONDS;
		$set = set_transient( self::TRANSIENT_PREFIX . $token, $payload, $ttl );

		return $set ? $token : false;
	}

	/**
	 * Öffentliche URL für ein Token.
	 *
	 * @param string $token Token-String.
	 * @return string
	 */
	public static function get_url( $token ) {
		$token = self::sanitize_token( $token );

		if ( '' === $token ) {
			return '';
		}

		if ( get_option( 'permalink_structure' ) ) {
			return home_url( '/' . self::REWRITE_SLUG . '/' . rawurlencode( $token ) . '/' );
		}

		return add_query_arg( self::QUERY_VAR, $token, home_url( '/' ) );
	}

	/**
	 * Token auflösen (Transient laden und Karte prüfen).
	 *
	 * @param string $token Token aus URL.
	 * @return array<string, mixed>|null card_id, message, created, sender_name.
	 */
	public static function resolve( $token ) {
		$token = self::sanitize_token( $token );

		if ( '' === $token ) {
			return null;
		}

		$payload = get_transient( self::TRANSIENT_PREFIX . $token );

		if ( ! is_array( $payload ) ) {
			return null;
		}

		$card_id = isset( $payload['card_id'] ) ? absint( $payload['card_id'] ) : 0;
		$message = isset( $payload['message'] ) ? trim( (string) $payload['message'] ) : '';

		if ( $card_id < 1 || '' === $message ) {
			return null;
		}

		if ( 'kudo_card' !== get_post_type( $card_id ) || 'publish' !== get_post_status( $card_id ) ) {
			return null;
		}

		return array(
			'card_id'     => $card_id,
			'message'     => $message,
			'created'     => isset( $payload['created'] ) ? (int) $payload['created'] : 0,
			'sender_name' => isset( $payload['sender_name'] ) ? sanitize_text_field( (string) $payload['sender_name'] ) : '',
		);
	}

	/**
	 * Token bereinigen (nur alphanumerisch).
	 *
	 * @param string $token Roher Token.
	 * @return string
	 */
	public static function sanitize_token( $token ) {
		$token = (string) $token;

		return preg_replace( '/[^a-zA-Z0-9]/', '', $token );
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