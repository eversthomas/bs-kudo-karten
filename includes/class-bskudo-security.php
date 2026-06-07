<?php
/**
 * Sicherheit: Nonce, Honeypot, Rate Limiting, Validierung.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prüft und bereinigt Versand-Anfragen.
 */
class BSKudo_Security {

	const NONCE_ACTION = 'bskudo_send_kudo';

	const TRANSIENT_PREFIX = 'bskudo_limit_';

	/**
	 * Mindestzeit zwischen Formular-Laden und Absenden (Sekunden).
	 */
	const MIN_FORM_SECONDS = 3;

	/**
	 * Maximales Alter des Formular-Zeitstempels (Sekunden).
	 */
	const MAX_FORM_AGE_SECONDS = DAY_IN_SECONDS;

	/**
	 * AJAX-Anfrage validieren.
	 *
	 * @param array<string, mixed> $data POST-Daten (bereits via wp_unslash).
	 * @return array<string, mixed>|WP_Error Bereinigte Daten oder Fehler.
	 */
	public function validate_request( $data ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'bskudo_invalid', __( 'Ungültige Anfrage.', 'bs-kudo-karten' ) );
		}

		if ( ! $this->verify_nonce( $data ) ) {
			return new WP_Error( 'bskudo_nonce', __( 'Sicherheitsprüfung fehlgeschlagen. Bitte lade die Seite neu.', 'bs-kudo-karten' ) );
		}

		if ( $this->is_honeypot_filled( $data ) ) {
			return new WP_Error( 'bskudo_spam', __( 'Die Anfrage konnte nicht verarbeitet werden.', 'bs-kudo-karten' ) );
		}

		if ( $this->is_form_submitted_too_fast( $data ) ) {
			return new WP_Error( 'bskudo_spam', __( 'Die Anfrage konnte nicht verarbeitet werden.', 'bs-kudo-karten' ) );
		}

		if ( $this->is_rate_limited() ) {
			return new WP_Error(
				'bskudo_rate_limit',
				sprintf(
					/* translators: %d: max sends per hour */
					__( 'Zu viele Versendungen. Bitte versuche es in einer Stunde erneut (max. %d pro Stunde).', 'bs-kudo-karten' ),
					BSKudo_Settings::get_rate_limit()
				)
			);
		}

		return $this->sanitize_submission( $data );
	}

	/**
	 * Nonce prüfen.
	 *
	 * @param array<string, mixed> $data POST-Daten.
	 * @return bool
	 */
	public function verify_nonce( $data ) {
		$nonce = isset( $data['bskudo_nonce'] ) ? sanitize_text_field( (string) $data['bskudo_nonce'] ) : '';

		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Honeypot: Feld muss leer sein.
	 *
	 * @param array<string, mixed> $data POST-Daten.
	 * @return bool True wenn Bot-Verdacht.
	 */
	public function is_honeypot_filled( $data ) {
		$hp = isset( $data['bskudo_hp'] ) ? trim( (string) $data['bskudo_hp'] ) : '';

		return '' !== $hp;
	}

	/**
	 * Formular-Zeitstempel prüfen (Bot-Schutz).
	 *
	 * @param array<string, mixed> $data POST-Daten.
	 * @return bool True wenn verdächtig.
	 */
	public function is_form_submitted_too_fast( $data ) {
		$ts = isset( $data['bskudo_form_ts'] ) ? absint( $data['bskudo_form_ts'] ) : 0;

		if ( $ts < 1 ) {
			return true;
		}

		$age = time() - $ts;

		return $age < self::MIN_FORM_SECONDS || $age > self::MAX_FORM_AGE_SECONDS;
	}

	/**
	 * Rate Limit für aktuelle IP prüfen.
	 *
	 * @return bool True wenn Limit erreicht.
	 */
	public function is_rate_limited() {
		$count = (int) get_transient( $this->get_rate_limit_key() );
		$limit = BSKudo_Settings::get_rate_limit();

		return $count >= $limit;
	}

	/**
	 * Erfolgreichen Versand für Rate Limit zählen.
	 */
	public function record_send() {
		$key   = $this->get_rate_limit_key();
		$count = (int) get_transient( $key );

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Transient-Key für IP-basiertes Limit.
	 *
	 * @return string
	 */
	private function get_rate_limit_key() {
		$ip = $this->get_client_ip();

		return self::TRANSIENT_PREFIX . md5( $ip );
	}

	/**
	 * Client-IP ermitteln (nur für Rate Limit, nicht speichern).
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Formulardaten bereinigen und prüfen.
	 *
	 * @param array<string, mixed> $data POST-Daten.
	 * @return array<string, mixed>|WP_Error
	 */
	private function sanitize_submission( $data ) {
		$card_id = isset( $data['bskudo_card_id'] ) ? absint( $data['bskudo_card_id'] ) : 0;
		$message = isset( $data['bskudo_message'] ) ? sanitize_textarea_field( (string) $data['bskudo_message'] ) : '';

		$sender_name     = isset( $data['bskudo_sender_name'] ) ? sanitize_text_field( (string) $data['bskudo_sender_name'] ) : '';
		$sender_email    = isset( $data['bskudo_sender_email'] ) ? sanitize_email( (string) $data['bskudo_sender_email'] ) : '';
		$recipient_name  = isset( $data['bskudo_recipient_name'] ) ? sanitize_text_field( (string) $data['bskudo_recipient_name'] ) : '';
		$recipient_email = isset( $data['bskudo_recipient_email'] ) ? sanitize_email( (string) $data['bskudo_recipient_email'] ) : '';

		$char_limit = BSKudo_Settings::get_char_limit();

		if ( $card_id < 1 || 'kudo_card' !== get_post_type( $card_id ) || 'publish' !== get_post_status( $card_id ) ) {
			return new WP_Error( 'bskudo_card', __( 'Bitte wähle eine gültige Kudo-Karte.', 'bs-kudo-karten' ) );
		}

		$message = trim( $message );

		if ( '' === $message ) {
			return new WP_Error( 'bskudo_message', __( 'Bitte schreibe einen Text für deine Karte.', 'bs-kudo-karten' ) );
		}

		if ( function_exists( 'mb_strlen' ) ) {
			if ( mb_strlen( $message ) > $char_limit ) {
				$message = mb_substr( $message, 0, $char_limit );
			}
		} elseif ( strlen( $message ) > $char_limit ) {
			$message = substr( $message, 0, $char_limit );
		}

		if ( '' === $sender_name ) {
			return new WP_Error( 'bskudo_names', __( 'Bitte fülle alle Namensfelder aus.', 'bs-kudo-karten' ) );
		}

		if ( ! is_email( $sender_email ) ) {
			return new WP_Error( 'bskudo_email', __( 'Bitte gib eine gültige Absender-E-Mail ein.', 'bs-kudo-karten' ) );
		}

		$send_to_self = ! empty( $data['bskudo_send_to_self'] );

		if ( $send_to_self && BSKudo_Settings::is_feature_enabled( 'enable_send_to_self' ) ) {
			$recipient_name  = $sender_name;
			$recipient_email = $sender_email;
		}

		if ( '' === $recipient_name ) {
			return new WP_Error( 'bskudo_names', __( 'Bitte fülle alle Namensfelder aus.', 'bs-kudo-karten' ) );
		}

		if ( ! is_email( $recipient_email ) ) {
			return new WP_Error( 'bskudo_email', __( 'Bitte gib eine gültige E-Mail-Adresse für den Empfänger ein.', 'bs-kudo-karten' ) );
		}

		$send_at = 0;
		$mode    = isset( $data['bskudo_send_mode'] ) ? sanitize_key( (string) $data['bskudo_send_mode'] ) : 'now';

		if ( 'later' === $mode && BSKudo_Settings::is_feature_enabled( 'enable_delayed_send' ) ) {
			$send_at = self::parse_send_at( $data );
			if ( is_wp_error( $send_at ) ) {
				return $send_at;
			}
		}

		return array(
			'card_id'         => $card_id,
			'message'         => $message,
			'sender_name'     => $sender_name,
			'sender_email'    => $sender_email,
			'recipient_name'  => $recipient_name,
			'recipient_email' => $recipient_email,
			'send_to_self'    => $send_to_self,
			'send_at'         => $send_at,
		);
	}

	/**
	 * Geplanten Versandzeitpunkt aus Formular parsen.
	 *
	 * @param array<string, mixed> $data POST-Daten.
	 * @return int|WP_Error Unix-Zeitstempel oder Fehler.
	 */
	private static function parse_send_at( $data ) {
		$raw = isset( $data['bskudo_send_at'] ) ? sanitize_text_field( (string) $data['bskudo_send_at'] ) : '';

		if ( '' === $raw ) {
			return new WP_Error( 'bskudo_schedule_empty', __( 'Bitte wähle Datum und Uhrzeit für den Versand.', 'bs-kudo-karten' ) );
		}

		$tz   = wp_timezone();
		$date = date_create( $raw, $tz );

		if ( false === $date ) {
			return new WP_Error( 'bskudo_schedule_invalid', __( 'Ungültiges Datum oder Uhrzeit.', 'bs-kudo-karten' ) );
		}

		$timestamp = $date->getTimestamp();
		$min_time  = time() + ( 5 * MINUTE_IN_SECONDS );
		$max_time  = time() + ( BSKudo_Settings::get_schedule_max_days() * DAY_IN_SECONDS );

		if ( $timestamp < $min_time ) {
			return new WP_Error(
				'bskudo_schedule_soon',
				__( 'Der Versandzeitpunkt muss mindestens 5 Minuten in der Zukunft liegen.', 'bs-kudo-karten' )
			);
		}

		if ( $timestamp > $max_time ) {
			return new WP_Error(
				'bskudo_schedule_far',
				sprintf(
					/* translators: %d: max days */
					__( 'Der Versand kann maximal %d Tage im Voraus geplant werden.', 'bs-kudo-karten' ),
					BSKudo_Settings::get_schedule_max_days()
				)
			);
		}

		return $timestamp;
	}
}
