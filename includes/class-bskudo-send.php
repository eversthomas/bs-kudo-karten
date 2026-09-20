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

		add_action( 'wp_ajax_' . BSKudo_Confirm::AJAX_ACTION, array( $this, 'handle_confirm' ) );
		add_action( 'wp_ajax_nopriv_' . BSKudo_Confirm::AJAX_ACTION, array( $this, 'handle_confirm' ) );

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

		if ( BSKudo_Settings::requires_sender_confirmation() ) {
			$this->handle_pending_confirmation( $data );
			return;
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
			sprintf(
				/* translators: %s: product name singular */
				__( 'Deine %s wurde erfolgreich versendet!', 'bs-kudo-karten' ),
				BSKudo_Settings::product_name_singular()
			),
			false,
			0,
			false
		);
	}

	/**
	 * Bestätigungslink aus E-Mail verarbeiten.
	 */
	public function handle_confirm() {
		$raw_token = isset( $_REQUEST['token'] ) ? wp_unslash( $_REQUEST['token'] ) : '';
		$token     = BSKudo_Confirm::sanitize_token( is_string( $raw_token ) ? $raw_token : '' );

		BSKudo_Debug::log(
			'confirm_request',
			array(
				'token_present' => '' !== $token,
			)
		);

		$data = BSKudo_Confirm::consume( $token );

		if ( null === $data ) {
			$this->render_confirm_result_page(
				false,
				__( 'Dieser Bestätigungslink ist ungültig, abgelaufen oder wurde bereits verwendet.', 'bs-kudo-karten' )
			);
			return;
		}

		BSKudo_Debug::log(
			'confirm_consume',
			array(
				'token_valid' => true,
				'scheduled'   => ! empty( $data['send_at'] ),
			)
		);

		$send_at = isset( $data['send_at'] ) ? (int) $data['send_at'] : 0;

		if ( $send_at > 0 && $send_at <= time() ) {
			$this->render_confirm_result_page(
				false,
				__( 'Der geplante Versandzeitpunkt liegt in der Vergangenheit. Bitte erstelle die Karte erneut über das Formular.', 'bs-kudo-karten' )
			);
			return;
		}

		if ( $send_at > 0 ) {
			$result = $this->execute_schedule( $data, $send_at );

			if ( is_wp_error( $result ) ) {
				BSKudo_Debug::log(
					'confirm_schedule_failed',
					array(
						'code'    => $result->get_error_code(),
						'message' => $result->get_error_message(),
					)
				);

				$this->render_confirm_result_page(
					false,
					__( 'Der Versand konnte nicht geplant werden. Bitte erstelle die Karte erneut über das Formular.', 'bs-kudo-karten' )
				);
				return;
			}

			$this->render_confirm_result_page(
				true,
				sprintf(
					/* translators: 1: product name singular, 2: date/time */
					__( 'Versand bestätigt. Deine %1$s wird am %2$s versendet.', 'bs-kudo-karten' ),
					BSKudo_Settings::product_name_singular(),
					BSKudo_Scheduler::format_send_at( $send_at )
				)
			);
			return;
		}

		$result = $this->mailer->send( $data );

		if ( is_wp_error( $result ) ) {
			BSKudo_Debug::log(
				'confirm_send_failed',
				array(
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				)
			);

			$this->render_confirm_result_page(
				false,
				__( 'Der Versand ist fehlgeschlagen. Bitte erstelle die Karte erneut über das Formular.', 'bs-kudo-karten' )
			);
			return;
		}

		$this->security->record_send();

		BSKudo_Debug::log( 'confirm_send_complete', array( 'status' => 'ok' ) );

		$this->render_confirm_result_page(
			true,
			sprintf(
				/* translators: %s: product name singular */
				__( 'Versand bestätigt. Deine %s ist unterwegs!', 'bs-kudo-karten' ),
				BSKudo_Settings::product_name_singular()
			)
		);
	}

	/**
	 * Bestätigungsmail an Absender senden statt sofort zu versenden.
	 *
	 * @param array<string, mixed> $data Validierte Versanddaten.
	 */
	private function handle_pending_confirmation( $data ) {
		if ( $this->security->is_confirm_request_rate_limited() ) {
			wp_send_json_error(
				array(
					'message' => __(
						'Zu viele Bestätigungsanfragen. Bitte versuche es später erneut.',
						'bs-kudo-karten'
					),
				),
				429
			);
		}

		$token = BSKudo_Confirm::create( $data );

		if ( false === $token ) {
			wp_send_json_error(
				array(
					'message' => __( 'Die Bestätigung konnte nicht erstellt werden. Bitte versuche es erneut.', 'bs-kudo-karten' ),
				),
				500
			);
		}

		$sent = $this->send_confirmation_mail( $data, $token );

		if ( is_wp_error( $sent ) ) {
			BSKudo_Confirm::consume( $token );

			wp_send_json_error(
				array(
					'message' => $sent->get_error_message(),
				),
				500
			);
		}

		$this->security->record_confirm_request();

		BSKudo_Debug::log(
			'confirm_mail_sent',
			array(
				'token_created' => true,
			)
		);

		$this->send_success_response(
			__(
				'Bitte bestätige den Versand über den Link, den wir dir gerade per E-Mail geschickt haben.',
				'bs-kudo-karten'
			),
			false,
			0,
			true
		);
	}

	/**
	 * Bestätigungs-E-Mail an die Absender-Adresse.
	 *
	 * @param array<string, mixed> $data  Versanddaten.
	 * @param string               $token Bestätigungs-Token.
	 * @return true|WP_Error
	 */
	private function send_confirmation_mail( $data, $token ) {
		$url = BSKudo_Confirm::get_url( $token );

		if ( '' === $url ) {
			return new WP_Error( 'bskudo_confirm_url', __( 'Bestätigungslink konnte nicht erzeugt werden.', 'bs-kudo-karten' ) );
		}

		$product     = BSKudo_Settings::product_name_singular();
		$recipient   = sanitize_text_field( (string) $data['recipient_name'] );
		$send_at     = isset( $data['send_at'] ) ? (int) $data['send_at'] : 0;
		$subject     = sprintf(
			/* translators: %s: product name singular */
			__( 'Bitte bestätige den Versand deiner %s', 'bs-kudo-karten' ),
			$product
		);
		$intro       = sprintf(
			/* translators: 1: product name singular, 2: recipient name */
			__( 'Du möchtest eine %1$s an %2$s senden.', 'bs-kudo-karten' ),
			$product,
			$recipient
		);
		$link_line   = sprintf(
			/* translators: %s: confirmation URL */
			__( 'Bitte bestätige den Versand über diesen Link (30 Minuten gültig): %s', 'bs-kudo-karten' ),
			$url
		);
		$schedule_line = '';

		if ( $send_at > 0 ) {
			$schedule_line = sprintf(
				/* translators: %s: formatted date/time */
				__( 'Geplanter Versand: %s', 'bs-kudo-karten' ),
				BSKudo_Scheduler::format_send_at( $send_at )
			) . "\n\n";
		}

		$body = $intro . "\n\n" . $schedule_line . $link_line . "\n\n" . __( 'Wenn du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.', 'bs-kudo-karten' );

		$from_name  = (string) BSKudo_Settings::get( 'general', 'sender_name', get_bloginfo( 'name' ) );
		$from_email = sanitize_email( (string) BSKudo_Settings::get( 'general', 'sender_email', get_option( 'admin_email' ) ) );

		if ( ! is_email( $from_email ) ) {
			$from_email = sanitize_email( (string) get_option( 'admin_email' ) );
		}

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		if ( is_email( $from_email ) ) {
			$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		}

		$to = sanitize_email( (string) $data['sender_email'] );

		if ( ! is_email( $to ) ) {
			return new WP_Error( 'bskudo_confirm_email', __( 'Ungültige Absender-E-Mail.', 'bs-kudo-karten' ) );
		}

		$ok = wp_mail( $to, $subject, $body, $headers );

		if ( ! $ok ) {
			return new WP_Error(
				'bskudo_confirm_mail',
				__( 'Die Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte versuche es erneut.', 'bs-kudo-karten' )
			);
		}

		return true;
	}

	/**
	 * Versand planen (JSON-Antwort für Wizard).
	 *
	 * @param array<string, mixed> $data    Versanddaten.
	 * @param int                  $send_at Unix-Zeitstempel.
	 */
	private function handle_scheduled_send( $data, $send_at ) {
		$result = $this->execute_schedule( $data, $send_at );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		$message = sprintf(
			/* translators: 1: product name singular, 2: date/time */
			__( 'Deine %1$s wird am %2$s versendet.', 'bs-kudo-karten' ),
			BSKudo_Settings::product_name_singular(),
			BSKudo_Scheduler::format_send_at( $send_at )
		);

		$this->send_success_response( $message, true, $send_at, false );
	}

	/**
	 * Geplanten Versand ausführen.
	 *
	 * @param array<string, mixed> $data    Versanddaten.
	 * @param int                  $send_at Unix-Zeitstempel.
	 * @return true|WP_Error
	 */
	private function execute_schedule( $data, $send_at ) {
		$mail_data = $data;
		unset( $mail_data['send_at'], $mail_data['send_to_self'] );

		$scheduled = $this->scheduler->schedule( $mail_data, $send_at );

		if ( is_wp_error( $scheduled ) ) {
			return $scheduled;
		}

		return true;
	}

	/**
	 * Einfache HTML-Seite nach Klick auf den Bestätigungslink.
	 *
	 * @param bool   $success Erfolg?
	 * @param string $message Meldung.
	 */
	private function render_confirm_result_page( $success, $message ) {
		$title = $success
			? __( 'Versand bestätigt', 'bs-kudo-karten' )
			: __( 'Bestätigung fehlgeschlagen', 'bs-kudo-karten' );

		$status_class = $success ? 'success' : 'error';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- statisches HTML mit esc_html/esc_url unten.
		echo '<!DOCTYPE html><html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . esc_html( $title ) . '</title><style>body{font-family:system-ui,sans-serif;background:#f5f5f5;margin:0;padding:2rem;color:#212121}.box{max-width:32rem;margin:0 auto;background:#fff;border-radius:12px;padding:2rem;box-shadow:0 2px 12px rgba(0,0,0,.08)}.success{border-left:4px solid #2e7d32}.error{border-left:4px solid #c62828}a{color:#335C70}</style></head><body><div class="box ' . esc_attr( $status_class ) . '"><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $message ) . '</p><p><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Zur Startseite', 'bs-kudo-karten' ) . '</a></p></div></body></html>';

		exit;
	}

	/**
	 * JSON-Erfolg ausgeben.
	 *
	 * @param string $message   Meldung.
	 * @param bool   $scheduled Geplant?
	 * @param int    $send_at   Zeitstempel.
	 * @param bool   $requires_confirmation Wartet auf E-Mail-Bestätigung?
	 */
	private function send_success_response( $message, $scheduled, $send_at, $requires_confirmation ) {
		$response = array(
			'message'               => $message,
			'scheduled'             => $scheduled,
			'sendAt'                => $send_at,
			'requiresConfirmation'  => $requires_confirmation,
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
