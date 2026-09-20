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

	const NONCE_ACTION = 'bskudo_confirm_send';

	const LOCK_OPTION_PREFIX = 'bskudo_confirm_lock_';

	const DONE_OPTION_PREFIX = 'bskudo_confirm_done_';

	const CRON_DELETE_OPTION = 'bskudo_delete_confirm_option';

	/**
	 * Lock-/Done-Optionen nach dieser Frist per Cron aus wp_options entfernen.
	 */
	const OPTION_CLEANUP_DELAY = DAY_IN_SECONDS;

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
	 * Token auflösen und Transient löschen (Einmalverwendung, atomar).
	 *
	 * @param string $token Roher Token.
	 * @return array<string, mixed>|WP_Error Payload, ungültig/abgelaufen (bskudo_confirm_invalid) oder bereits verarbeitet (bskudo_confirm_already).
	 */
	public static function consume( $token ) {
		$token = self::sanitize_token( $token );

		if ( '' === $token ) {
			return new WP_Error(
				'bskudo_confirm_invalid',
				__( 'Dieser Bestätigungslink ist ungültig, abgelaufen oder wurde bereits verwendet.', 'bs-kudo-karten' )
			);
		}

		$lock_option = self::get_lock_option_name( $token );
		$done_option = self::get_done_option_name( $token );

		if ( false !== get_option( $done_option, false ) ) {
			return new WP_Error(
				'bskudo_confirm_already',
				__( 'Diese Bestätigung wurde bereits verarbeitet.', 'bs-kudo-karten' )
			);
		}

		if ( ! self::add_ephemeral_option( $lock_option, time() ) ) {
			return new WP_Error(
				'bskudo_confirm_already',
				__( 'Diese Bestätigung wurde bereits verarbeitet.', 'bs-kudo-karten' )
			);
		}

		$key     = self::TRANSIENT_PREFIX . $token;
		$payload = get_transient( $key );

		if ( ! is_array( $payload ) ) {
			delete_option( $lock_option );

			if ( false !== get_option( $done_option, false ) ) {
				return new WP_Error(
					'bskudo_confirm_already',
					__( 'Diese Bestätigung wurde bereits verarbeitet.', 'bs-kudo-karten' )
				);
			}

			return new WP_Error(
				'bskudo_confirm_invalid',
				__( 'Dieser Bestätigungslink ist ungültig oder abgelaufen.', 'bs-kudo-karten' )
			);
		}

		delete_transient( $key );
		self::add_ephemeral_option( $done_option, time() );

		delete_option( $lock_option );

		$normalized = self::normalize_payload( $payload );

		if ( null === $normalized ) {
			return new WP_Error(
				'bskudo_confirm_invalid',
				__( 'Dieser Bestätigungslink ist ungültig oder abgelaufen.', 'bs-kudo-karten' )
			);
		}

		return $normalized;
	}

	/**
	 * Option-Name für Consume-Lock (add_option ist DB-seitig atomar).
	 *
	 * @param string $token Bereinigter Token.
	 * @return string
	 */
	private static function get_lock_option_name( $token ) {
		return self::LOCK_OPTION_PREFIX . md5( $token );
	}

	/**
	 * Option-Name: Token wurde verbraucht (Doppel-POST-Schutz).
	 *
	 * @param string $token Bereinigter Token.
	 * @return string
	 */
	private static function get_done_option_name( $token ) {
		return self::DONE_OPTION_PREFIX . md5( $token );
	}

	/**
	 * Kurzlebige Option anlegen (autoload = no) und verzögertes Löschen planen.
	 *
	 * @param string $option_name Option-Key.
	 * @param mixed  $value       Wert.
	 * @return bool True wenn neu angelegt.
	 */
	private static function add_ephemeral_option( $option_name, $value ) {
		// Viertes Argument: autoload „no“ (WP akzeptiert auch false ab 6.6).
		$added = add_option( $option_name, $value, '', 'no' );

		if ( $added ) {
			self::schedule_option_cleanup( $option_name );
		}

		return $added;
	}

	/**
	 * Einmaliges Cron-Event zum Entfernen einer Lock-/Done-Option.
	 *
	 * @param string $option_name Option-Key.
	 */
	private static function schedule_option_cleanup( $option_name ) {
		if ( ! self::is_managed_option_name( $option_name ) ) {
			return;
		}

		wp_schedule_single_event(
			time() + self::OPTION_CLEANUP_DELAY,
			self::CRON_DELETE_OPTION,
			array( $option_name )
		);
	}

	/**
	 * Prüft, ob ein Option-Name zu Lock/Done gehört (Cron-Härtung).
	 *
	 * @param string $option_name Option-Key.
	 * @return bool
	 */
	public static function is_managed_option_name( $option_name ) {
		$option_name = (string) $option_name;

		if ( '' === $option_name ) {
			return false;
		}

		return 0 === strpos( $option_name, self::LOCK_OPTION_PREFIX )
			|| 0 === strpos( $option_name, self::DONE_OPTION_PREFIX );
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

/**
 * Lock-/Done-Optionen aus wp_options entfernen (TTL-Cleanup via wp_schedule_single_event).
 *
 * @param string $option_name Option-Key.
 */
function bskudo_delete_confirm_option( $option_name ) {
	$option_name = (string) $option_name;

	if ( ! BSKudo_Confirm::is_managed_option_name( $option_name ) ) {
		return;
	}

	delete_option( $option_name );
}

add_action( BSKudo_Confirm::CRON_DELETE_OPTION, 'bskudo_delete_confirm_option' );
