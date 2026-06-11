<?php
/**
 * AJAX-Endpunkt für den Kudo-Karten-Versand.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verbindet Security, Mailer, Scheduler und wp_ajax.
 */
class BSKudo_Send {

	const AJAX_ACTION = 'bskudo_send_kudo';

	/**
	 * @var BSKudo_Security
	 */
	private $security;

	/**
	 * @var BSKudo_Mailer
	 */
	private $mailer;

	/**
	 * @var BSKudo_Scheduler
	 */
	private $scheduler;

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		$this->security  = new BSKudo_Security();
		$this->mailer    = new BSKudo_Mailer();
		$this->scheduler = new BSKudo_Scheduler();
	}

	/**
	 * Hooks registrieren.
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( $this, 'handle' ) );
		$this->scheduler->register();
	}

	/**
	 * Versand-Anfrage verarbeiten.
	 */
	public function handle() {
		$nonce = isset( $_POST['bskudo_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bskudo_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, BSKudo_Security::NONCE_ACTION ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sicherheitsprüfung fehlgeschlagen. Bitte lade die Seite neu.', 'bs-kudo-karten' ),
				),
				403
			);
		}

		BSKudo_Debug::log(
			'ajax_request',
			array(
				'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'post_keys'   => array_keys( wp_unslash( $_POST ) ),
				'environment' => BSKudo_Debug::get_environment_snapshot(),
			)
		);

		$data = $this->security->validate_request( wp_unslash( $_POST ) );

		if ( is_wp_error( $data ) ) {
			BSKudo_Debug::log(
				'ajax_rejected',
				array(
					'code'    => $data->get_error_code(),
					'message' => $data->get_error_message(),
				)
			);

			wp_send_json_error(
				array(
					'message' => $data->get_error_message(),
				),
				400
			);
		}

		$send_at = isset( $data['send_at'] ) ? (int) $data['send_at'] : 0;

		if ( $send_at > 0 ) {
			$this->handle_scheduled_send( $data, $send_at );
			return;
		}

		$result = $this->mailer->send( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				500
			);
		}

		$this->security->record_send();

		$this->send_success_response(
			__( 'Deine Kudo-Karte wurde erfolgreich versendet!', 'bs-kudo-karten' ),
			false,
			0
		);
	}

	/**
	 * Versand planen statt sofort senden.
	 *
	 * @param array<string, mixed> $data    Versanddaten.
	 * @param int                  $send_at Unix-Zeitstempel.
	 */
	private function handle_scheduled_send( $data, $send_at ) {
		$mail_data = $data;
		unset( $mail_data['send_at'], $mail_data['send_to_self'] );

		$scheduled = $this->scheduler->schedule( $mail_data, $send_at );

		if ( is_wp_error( $scheduled ) ) {
			wp_send_json_error(
				array(
					'message' => $scheduled->get_error_message(),
				),
				400
			);
		}

		$message = sprintf(
			/* translators: %s: date/time */
			__( 'Deine Kudo-Karte wird am %s versendet.', 'bs-kudo-karten' ),
			BSKudo_Scheduler::format_send_at( $send_at )
		);

		$this->send_success_response( $message, true, $send_at );
	}

	/**
	 * JSON-Erfolg ausgeben.
	 *
	 * @param string $message   Meldung.
	 * @param bool   $scheduled Geplant?
	 * @param int    $send_at   Zeitstempel.
	 */
	private function send_success_response( $message, $scheduled, $send_at ) {
		$response = array(
			'message'   => $message,
			'scheduled' => $scheduled,
			'sendAt'    => $send_at,
		);

		if ( BSKudo_Debug::is_enabled() ) {
			$response['debug'] = array(
				'log_file'   => BSKudo_Debug::get_log_path(),
				'html_file'  => BSKudo_Debug::get_html_path(),
				'local_hint' => BSKudo_Debug::get_environment_snapshot()['local_note'] ?? '',
			);
		}

		wp_send_json_success( $response );
	}
}
