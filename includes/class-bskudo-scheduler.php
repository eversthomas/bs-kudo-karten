<?php
/**
 * Verzögerter Versand per WP-Cron.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plant Kudo-Versendungen; speichert Aufträge kurzzeitig als Transient (nur bis Versand).
 */
class BSKudo_Scheduler {

	const CRON_HOOK = 'bskudo_process_scheduled_send';

	const TRANSIENT_PREFIX = 'bskudo_job_';

	/**
	 * Hooks registrieren.
	 */
	public function register() {
		add_action( self::CRON_HOOK, array( $this, 'run_job' ), 10, 1 );
	}

	/**
	 * Versand zu einem Zeitpunkt planen.
	 *
	 * @param array<string, mixed> $data      Versanddaten (ohne send_at).
	 * @param int                  $send_at   Unix-Zeitstempel.
	 * @return string|WP_Error Job-ID oder Fehler.
	 */
	public function schedule( $data, $send_at ) {
		$send_at = absint( $send_at );

		if ( $send_at <= time() ) {
			return new WP_Error( 'bskudo_schedule_past', __( 'Der gewählte Zeitpunkt liegt in der Vergangenheit.', 'bs-kudo-karten' ) );
		}

		$job_id = wp_generate_password( 24, false, false );
		$ttl    = ( $send_at - time() ) + DAY_IN_SECONDS;

		$stored = set_transient( self::TRANSIENT_PREFIX . $job_id, $data, $ttl );

		if ( ! $stored ) {
			return new WP_Error( 'bskudo_schedule_store', __( 'Der Versand konnte nicht geplant werden.', 'bs-kudo-karten' ) );
		}

		wp_schedule_single_event( $send_at, self::CRON_HOOK, array( $job_id ) );

		return $job_id;
	}

	/**
	 * Geplanten Versand ausführen.
	 *
	 * @param string $job_id Job-Kennung.
	 */
	public function run_job( $job_id ) {
		$job_id = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $job_id );

		if ( '' === $job_id ) {
			return;
		}

		$data = get_transient( self::TRANSIENT_PREFIX . $job_id );

		delete_transient( self::TRANSIENT_PREFIX . $job_id );

		if ( ! is_array( $data ) ) {
			return;
		}

		$mailer = new BSKudo_Mailer();
		$result = $mailer->send( $data );

		if ( ! is_wp_error( $result ) ) {
			$security = new BSKudo_Security();
			$security->record_send();
		}

		BSKudo_Debug::log(
			'scheduled_send',
			array(
				'job_id'  => $job_id,
				'success' => ! is_wp_error( $result ),
				'error'   => is_wp_error( $result ) ? $result->get_error_message() : '',
			)
		);
	}

	/**
	 * Menschenlesbares Datum für Bestätigungen.
	 *
	 * @param int $timestamp Unix-Zeit.
	 * @return string
	 */
	public static function format_send_at( $timestamp ) {
		return wp_date( 'd.m.Y, H:i', $timestamp ) . ' ' . __( 'Uhr', 'bs-kudo-karten' );
	}
}
