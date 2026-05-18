<?php
/**
 * Versand von Kudo-Karten per wp_mail().
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * E-Mail-Versand (Phase 4: Text-HTML; Bilder in Phase 6 als JPG).
 */
class BSKudo_Mailer {

	/**
	 * Absender für den aktuellen wp_mail()-Aufruf.
	 *
	 * @var array{email: string, name: string}|null
	 */
	private $mail_from = null;

	/**
	 * Letzter wp_mail_failed-Fehler.
	 *
	 * @var WP_Error|null
	 */
	private $last_mail_error = null;

	/**
	 * Plain-Text-Alternative für den aktuellen Versand.
	 *
	 * @var string
	 */
	private $plain_body = '';

	/**
	 * Kudo-Karte per E-Mail versenden.
	 *
	 * @param array<string, mixed> $data Bereinigte Versanddaten.
	 * @return true|WP_Error
	 */
	public function send( $data ) {
		BSKudo_Debug::log(
			'send_start',
			array(
				'environment' => BSKudo_Debug::get_environment_snapshot(),
				'card_id'     => isset( $data['card_id'] ) ? (int) $data['card_id'] : 0,
				'recipient'   => isset( $data['recipient_email'] ) ? (string) $data['recipient_email'] : '',
				'sender'      => isset( $data['sender_email'] ) ? (string) $data['sender_email'] : '',
			)
		);

		$card = get_post( (int) $data['card_id'] );

		if ( ! $card instanceof WP_Post ) {
			BSKudo_Debug::log( 'send_abort', array( 'reason' => 'card_not_found' ) );
			return new WP_Error( 'bskudo_card', __( 'Karte nicht gefunden.', 'bs-kudo-karten' ) );
		}

		$card_title = get_the_title( $card );

		$branding = get_post_meta( $card->ID, '_bskudo_back_branding', true );
		if ( ! is_string( $branding ) || '' === trim( $branding ) ) {
			$legacy = get_post_meta( $card->ID, '_bskudo_impulse_text', true );
			$branding = is_string( $legacy ) ? $legacy : '';
		}
		if ( ! is_string( $branding ) || '' === trim( $branding ) ) {
			$branding = (string) BSKudo_Settings::get( 'branding', 'branding_text', '' );
		}

		$subject  = $this->build_subject( $data, $card_title );
		$view_url = '';
		$token    = BSKudo_Token::create( (int) $data['card_id'], (string) $data['message'] );

		if ( $token ) {
			$view_url = BSKudo_Token::get_url( $token );
		}

		$card_image_id = (int) get_post_thumbnail_id( $card->ID );
		$card_img_src  = '';

		$card_images = BSKudo_Card_Renderer::render_for_mail( (int) $data['card_id'], (string) $data['message'], $branding );

		if ( $card_images && ! empty( $card_images['front'] ) ) {
			// Gerenderte Vorderseite (Text eingebrannt) als data-URI.
			$card_img_src = 'data:image/jpeg;base64,' . $card_images['front'];
		} elseif ( $card_image_id ) {
			$card_img_src = $this->get_attachment_data_uri( $card_image_id );
		}

		$logo_id  = (int) BSKudo_Settings::get( 'branding', 'logo_id', 0 );
		$logo_src = $logo_id ? $this->get_attachment_data_uri( $logo_id ) : '';

		$qr_data_uri = '';
		if ( $view_url && BSKudo_Settings::is_feature_enabled( 'show_qr_in_mail' ) ) {
			$qr_data_uri = BSKudo_QR::get_data_uri( $view_url );
		}

		$body = $this->build_body(
			array(
				'recipient_name'   => $data['recipient_name'],
				'sender_name'      => $data['sender_name'],
				'sender_display'   => (string) BSKudo_Settings::get( 'general', 'sender_name', get_bloginfo( 'name' ) ),
				'view_url'         => $view_url,
				'card_img_src'     => $card_img_src,
				'logo_src'         => $logo_src,
				'mail_footer_text' => (string) BSKudo_Settings::get( 'branding', 'mail_footer_text', '' ),
				'qr_data_uri'      => $qr_data_uri,
				'token_ttl_days'   => (string) BSKudo_Settings::get_token_ttl_days(),
			)
		);

		$this->plain_body = $this->build_plain_body( $data, $card_title, $view_url );

		if ( '' === trim( wp_strip_all_tags( $body ) ) ) {
			BSKudo_Debug::log( 'send_abort', array( 'reason' => 'empty_body' ) );
			return new WP_Error(
				'bskudo_mail_body',
				__( 'Die E-Mail konnte nicht erstellt werden. Bitte kontaktiere den Seitenbetreiber.', 'bs-kudo-karten' )
			);
		}

		BSKudo_Debug::save_mail_html( $body );

		$headers = $this->build_headers( $data );

		$sent = $this->dispatch_mail( $data['recipient_email'], $subject, $body, $headers, 'recipient' );

		if ( is_wp_error( $sent ) ) {
			BSKudo_Debug::log(
				'send_failed',
				array(
					'target'  => 'recipient',
					'code'    => $sent->get_error_code(),
					'message' => $sent->get_error_message(),
				)
			);
			return $sent;
		}

		if ( BSKudo_Settings::get( 'general', 'copy_to_sender', false ) ) {
			$copy_subject = sprintf(
				/* translators: %s: recipient name */
				__( 'Kopie deiner Kudo-Karte an %s', 'bs-kudo-karten' ),
				$data['recipient_name']
			);

			$copy_result = $this->dispatch_mail( $data['sender_email'], $copy_subject, $body, $headers, 'sender_copy' );

			if ( is_wp_error( $copy_result ) ) {
				BSKudo_Debug::log(
					'copy_failed',
					array(
						'code'    => $copy_result->get_error_code(),
						'message' => $copy_result->get_error_message(),
					)
				);
			}
		}

		BSKudo_Debug::log( 'send_complete', array( 'status' => 'ok' ) );

		return true;
	}

	/**
	 * wp_mail() mit Absender-Filtern und Fehlerauswertung.
	 *
	 * @param string               $to      Empfänger.
	 * @param string               $subject Betreff.
	 * @param string               $body    HTML-Inhalt.
	 * @param array<int, string>   $headers Header-Zeilen.
	 * @param string               $target  Log-Kennung (recipient, sender_copy).
	 * @return true|WP_Error
	 */
	private function dispatch_mail( $to, $subject, $body, $headers, $target = 'recipient' ) {
		$from_email = (string) BSKudo_Settings::get( 'general', 'sender_email', get_option( 'admin_email' ) );
		$from_name  = (string) BSKudo_Settings::get( 'general', 'sender_name', get_bloginfo( 'name' ) );

		if ( ! is_email( $from_email ) ) {
			$from_email = get_option( 'admin_email' );
		}

		$this->mail_from       = array(
			'email' => $from_email,
			'name'  => wp_specialchars_decode( $from_name, ENT_QUOTES ),
		);
		$this->last_mail_error = null;

		add_filter( 'wp_mail_from', array( $this, 'filter_mail_from' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'filter_mail_content_type' ) );
		add_action( 'phpmailer_init', array( $this, 'set_plain_body' ) );
		add_action( 'wp_mail_failed', array( $this, 'capture_mail_failed' ) );

		BSKudo_Debug::log(
			'wp_mail_before',
			array(
				'target'  => $target,
				'to'      => $to,
				'subject' => $subject,
				'from'    => $this->mail_from,
				'headers' => $headers,
				'body_bytes' => strlen( $body ),
			)
		);

		$sent = wp_mail( $to, $subject, $body, $headers );

		$phpmailer_info = BSKudo_Debug::get_phpmailer_snapshot();

		BSKudo_Debug::log(
			'wp_mail_after',
			array(
				'target'          => $target,
				'wp_mail_return'  => $sent,
				'wp_mail_failed'  => $this->last_mail_error instanceof WP_Error
					? $this->last_mail_error->get_error_message()
					: null,
				'phpmailer'       => $phpmailer_info,
			)
		);

		remove_filter( 'wp_mail_from', array( $this, 'filter_mail_from' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'filter_mail_content_type' ) );
		remove_action( 'phpmailer_init', array( $this, 'set_plain_body' ) );
		remove_action( 'wp_mail_failed', array( $this, 'capture_mail_failed' ) );

		$this->mail_from  = null;
		$this->plain_body = '';

		if ( ! $sent || $this->last_mail_error ) {
			return new WP_Error(
				'bskudo_mail',
				$this->get_mail_error_message()
			);
		}

		global $phpmailer;

		if ( isset( $phpmailer ) && is_object( $phpmailer ) && ! empty( $phpmailer->ErrorInfo ) ) {
			return new WP_Error(
				'bskudo_mail',
				$this->get_mail_error_message( $phpmailer->ErrorInfo )
			);
		}

		return true;
	}

	/**
	 * Fehlermeldung für den Versand.
	 *
	 * @param string $detail Optionaler technischer Hinweis (nur bei WP_DEBUG sichtbar).
	 * @return string
	 */
	private function get_mail_error_message( $detail = '' ) {
		$message = __( 'Die E-Mail konnte nicht versendet werden. Bitte versuche es später erneut oder kontaktiere den Seitenbetreiber.', 'bs-kudo-karten' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && '' !== $detail ) {
			$message .= ' [' . $detail . ']';
		}

		if ( $this->last_mail_error instanceof WP_Error && $this->last_mail_error->get_error_message() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$message .= ' [' . $this->last_mail_error->get_error_message() . ']';
			}
		}

		return $message;
	}

	/**
	 * wp_mail_failed protokollieren.
	 *
	 * @param WP_Error $error Fehlerobjekt.
	 */
	public function capture_mail_failed( $error ) {
		if ( $error instanceof WP_Error ) {
			$this->last_mail_error = $error;
		}
	}

	/**
	 * Absender-E-Mail für wp_mail().
	 *
	 * @param string $email Standard-Absender.
	 * @return string
	 */
	public function filter_mail_from( $email ) {
		if ( $this->mail_from && is_email( $this->mail_from['email'] ) ) {
			return $this->mail_from['email'];
		}

		return $email;
	}

	/**
	 * Absender-Name für wp_mail().
	 *
	 * @param string $name Standard-Name.
	 * @return string
	 */
	public function filter_mail_from_name( $name ) {
		if ( $this->mail_from && '' !== $this->mail_from['name'] ) {
			return $this->mail_from['name'];
		}

		return $name;
	}

	/**
	 * HTML als Mail-Content-Type.
	 *
	 * @param string $type Aktueller Typ.
	 * @return string
	 */
	public function filter_mail_content_type( $type ) {
		return 'text/html';
	}

	/**
	 * Plain-Text-Alternative an PHPMailer übergeben.
	 *
	 * @param PHPMailer $phpmailer Mailer-Instanz.
	 */
	public function set_plain_body( $phpmailer ) {
		if ( '' !== $this->plain_body && is_object( $phpmailer ) && property_exists( $phpmailer, 'AltBody' ) ) {
			$phpmailer->AltBody = $this->plain_body;
		}
	}

	/**
	 * Plain-Text-Inhalt für multipart/alternative.
	 *
	 * @param array<string, mixed> $data       Versanddaten.
	 * @param string               $card_title Kartentitel.
	 * @param string               $view_url   Webansicht-URL.
	 * @return string
	 */
	private function build_plain_body( $data, $card_title, $view_url ) {
		$lines = array(
			sprintf(
				/* translators: 1: recipient, 2: sender */
				__( 'Hallo %1$s, %2$s hat dir eine Kudo-Karte geschickt:', 'bs-kudo-karten' ),
				$data['recipient_name'],
				$data['sender_name']
			),
			'',
		);

		if ( $card_title ) {
			$lines[] = $card_title;
			$lines[] = '';
		}

		$lines[] = '"' . $data['message'] . '"';
		$lines[] = '';

		if ( $view_url ) {
			$lines[] = __( 'Karte im Browser ansehen:', 'bs-kudo-karten' );
			$lines[] = $view_url;
			$lines[] = '';
		}

		$footer = (string) BSKudo_Settings::get( 'branding', 'mail_footer_text', '' );
		if ( '' !== trim( $footer ) ) {
			$lines[] = $footer;
			$lines[] = '';
		}

		if ( BSKudo_Settings::get( 'branding', 'footer_powered', true ) ) {
			$lines[] = 'Powered by BS Kudo Karten · bezugssysteme.de';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Betreff aus Vorlage mit Platzhaltern.
	 *
	 * @param array<string, mixed> $data       Versanddaten.
	 * @param string               $card_title Kartentitel.
	 * @return string
	 */
	private function build_subject( $data, $card_title ) {
		$template = (string) BSKudo_Settings::get( 'general', 'subject_template', '' );

		$subject = str_replace(
			array( '{sender}', '{recipient}', '{card}' ),
			array( $data['sender_name'], $data['recipient_name'], $card_title ),
			$template
		);

		return sanitize_text_field( $subject );
	}

	/**
	 * Medien-Anhang als data-URI (inline, keine externe Bild-URL).
	 *
	 * @param int $attachment_id Attachment-ID.
	 * @return string data-URI oder leer.
	 */
	private function get_attachment_data_uri( $attachment_id ) {
		$attachment_id = absint( $attachment_id );

		if ( $attachment_id < 1 ) {
			return '';
		}

		$img_path = get_attached_file( $attachment_id );

		if ( ! $img_path || ! is_readable( $img_path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$img_data = file_get_contents( $img_path );

		if ( false === $img_data ) {
			return '';
		}

		$img_mime = get_post_mime_type( $attachment_id );

		if ( ! is_string( $img_mime ) || '' === $img_mime ) {
			$img_mime = 'image/jpeg';
		}

		return 'data:' . $img_mime . ';base64,' . base64_encode( $img_data );
	}

	/**
	 * HTML-Mail aus Template laden.
	 *
	 * @param array<string, string> $vars Template-Variablen.
	 * @return string
	 */
	private function build_body( $vars ) {
		$vars['site_name']    = get_bloginfo( 'name' );
		$vars['show_powered'] = BSKudo_Settings::get( 'branding', 'footer_powered', true ) ? '1' : '';
		$vars['powered_text'] = 'Powered by BS Kudo Karten · bezugssysteme.de';

		ob_start();
		// Variablen für template-mail.php bereitstellen.
		extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include BSKUDO_PATH . 'mail/template-mail.php';
		$html = (string) ob_get_clean();

		/**
		 * HTML-Mail-Inhalt anpassen.
		 *
		 * @param string               $html Mail-HTML.
		 * @param array<string, mixed> $vars Template-Variablen.
		 */
		return (string) apply_filters( 'bskudo_mail_html', $html, $vars );
	}

	/**
	 * Mail-Header (Reply-To, Content-Type).
	 *
	 * @param array<string, mixed> $data Versanddaten.
	 * @return array<int, string>
	 */
	private function build_headers( $data ) {
		$reply_name  = sanitize_text_field( $data['sender_name'] );
		$reply_email = sanitize_email( $data['sender_email'] );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . $this->format_mailbox( $reply_name, $reply_email ),
		);

		/**
		 * Zusätzliche wp_mail-Header.
		 *
		 * @param array<int, string>   $headers Header-Zeilen.
		 * @param array<string, mixed> $data    Versanddaten.
		 */
		return (array) apply_filters( 'bskudo_mail_headers', $headers, $data );
	}

	/**
	 * Mailbox für Header formatieren (Name + E-Mail).
	 *
	 * @param string $name  Anzeigename.
	 * @param string $email E-Mail-Adresse.
	 * @return string
	 */
	private function format_mailbox( $name, $email ) {
		if ( ! is_email( $email ) ) {
			return '';
		}

		if ( '' === $name ) {
			return $email;
		}

		if ( function_exists( 'mb_encode_mimeheader' ) ) {
			$name = mb_encode_mimeheader( $name, 'UTF-8', 'B', "\r\n", strlen( 'Reply-To: ' ) );
		}

		return sprintf( '%s <%s>', $name, $email );
	}
}
