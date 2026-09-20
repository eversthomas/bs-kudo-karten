<?php
/**
 * Debug-Logging für Mail-Versand (lokale Entwicklung / WP_DEBUG).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schreibt Versand-Details in ein geschütztes Verzeichnis unter wp-content/uploads.
 */
class BSKudo_Debug {

	const UPLOAD_SUBDIR = 'bskudo-debug-private';

	const LOG_FILENAME  = 'mail.log';

	const HTML_FILENAME = 'last-mail.html';

	/**
	 * Ob Mail-Debug aktiv ist.
	 *
	 * Aktivierung (erste zutreffende Regel):
	 * - define( 'BSKUDO_MAIL_DEBUG', true ); in wp-config.php
	 * - lokale Hostnamen (.local, .test, localhost, 127.0.0.1)
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( defined( 'BSKUDO_MAIL_DEBUG' ) ) {
			return (bool) BSKUDO_MAIL_DEBUG;
		}

		return self::is_local_site();
	}

	/**
	 * Verzeichnis für Debug-Dateien.
	 *
	 * @return string Absoluter Pfad mit trailing slash.
	 */
	public static function get_dir() {
		$upload = wp_upload_dir();

		if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
			return trailingslashit( BSKUDO_PATH ) . 'debug/';
		}

		return trailingslashit( $upload['basedir'] ) . self::UPLOAD_SUBDIR . '/';
	}

	/**
	 * Pfad zur Log-Datei.
	 *
	 * @return string
	 */
	public static function get_log_path() {
		return self::get_dir() . self::LOG_FILENAME;
	}

	/**
	 * Pfad zur zuletzt erzeugten HTML-Mail.
	 *
	 * @return string
	 */
	public static function get_html_path() {
		return self::get_dir() . self::HTML_FILENAME;
	}

	/**
	 * Eintrag ins Log schreiben.
	 *
	 * @param string               $event   Ereignis-Name.
	 * @param array<string, mixed> $context Zusatzdaten.
	 */
	public static function log( $event, $context = array() ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		self::ensure_dir();

		$line = wp_json_encode(
			array(
				'time'    => gmdate( 'Y-m-d H:i:s' ) . ' UTC',
				'event'   => (string) $event,
				'context' => $context,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		if ( false === $line ) {
			$line = '{"time":"' . gmdate( 'c' ) . '","event":"' . $event . '","context":"encode_failed"}';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( self::get_log_path(), $line . PHP_EOL, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Letzte HTML-Mail speichern (Vorschau im Browser / Editor).
	 *
	 * @param string $html Mail-HTML.
	 */
	public static function save_mail_html( $html ) {
		if ( ! self::is_enabled() || '' === $html ) {
			return;
		}

		self::ensure_dir();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( self::get_html_path(), $html, LOCK_EX );
	}

	/**
	 * Umgebungs- und Mail-System-Infos sammeln.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_environment_snapshot() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return array(
			'site_url'        => home_url(),
			'host'            => $host ? (string) $host : '',
			'is_local_site'   => self::is_local_site(),
			'wp_debug'        => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'bskudo_debug'    => self::is_enabled(),
			'php_version'     => PHP_VERSION,
			'admin_email'     => get_option( 'admin_email' ),
			'mail_function'   => function_exists( 'mail' ) ? 'available' : 'missing',
			'smtp_plugin'     => self::detect_smtp_plugin(),
			'local_note'      => self::is_local_site()
				? 'Lokale Umgebung: wp_mail() liefert oft true, obwohl keine E-Mail im Postfach ankommt. SMTP-Plugin oder Mailhog/Mailpit empfohlen.'
				: '',
		);
	}

	/**
	 * PHPMailer-Zustand nach wp_mail() auslesen.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_phpmailer_snapshot() {
		global $phpmailer;

		if ( ! isset( $phpmailer ) || ! is_object( $phpmailer ) ) {
			return array( 'available' => false );
		}

		$info = array(
			'available'  => true,
			'mailer'     => isset( $phpmailer->Mailer ) ? (string) $phpmailer->Mailer : '',
			'from'       => isset( $phpmailer->From ) ? (string) $phpmailer->From : '',
			'from_name'  => isset( $phpmailer->FromName ) ? (string) $phpmailer->FromName : '',
			'host'       => isset( $phpmailer->Host ) ? (string) $phpmailer->Host : '',
			'port'       => isset( $phpmailer->Port ) ? (int) $phpmailer->Port : 0,
			'error_info' => isset( $phpmailer->ErrorInfo ) ? (string) $phpmailer->ErrorInfo : '',
		);

		if ( method_exists( $phpmailer, 'getSentMIMEMessage' ) ) {
			$mime = $phpmailer->getSentMIMEMessage();
			$info['mime_size_bytes'] = is_string( $mime ) ? strlen( $mime ) : 0;
		}

		return $info;
	}

	/**
	 * Log-Datei leeren (nur wenn Debug aktiv).
	 */
	public static function clear_log() {
		if ( ! self::is_enabled() || ! file_exists( self::get_log_path() ) ) {
			return;
		}

		wp_delete_file( self::get_log_path() );
	}

	/**
	 * Letzte N Zeilen der Log-Datei.
	 *
	 * @param int $lines Anzahl Zeilen.
	 * @return string
	 */
	public static function tail_log( $lines = 15 ) {
		$path = self::get_log_path();

		if ( ! file_exists( $path ) ) {
			return '';
		}

		$content = file_get_contents( $path );
		if ( false === $content || '' === $content ) {
			return '';
		}

		$all = array_filter( explode( "\n", trim( $content ) ) );
		$slice = array_slice( $all, -1 * max( 1, $lines ) );

		return implode( "\n", $slice );
	}

	/**
	 * Mail-Debug-Log im Admin (Tab Sicherheit) anzeigen.
	 */
	public static function render_admin_log_view() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$enabled  = self::is_enabled();
		$log_path = self::get_log_path();
		$tail     = $enabled ? self::tail_log( 20 ) : '';
		?>
		<div class="card">
			<div class="card-head">
				<h2><?php esc_html_e( 'Mail-Debug-Protokoll', 'bs-kudo-karten' ); ?></h2>
			</div>
			<div class="card-body">
				<?php if ( ! $enabled ) : ?>
					<p class="fhint">
						<?php esc_html_e( 'Mail-Debug ist derzeit inaktiv. Aktivierung nur über define( \'BSKUDO_MAIL_DEBUG\', true ); in wp-config.php oder auf lokalen Entwicklungs-URLs (.local, .test, localhost).', 'bs-kudo-karten' ); ?>
					</p>
				<?php else : ?>
					<p class="fhint">
						<?php esc_html_e( 'Enthält personenbezogene Daten (E-Mail-Adressen, Versandkontext). Nur für Administratoren sichtbar – nicht per direkter URL im Browser aufrufen.', 'bs-kudo-karten' ); ?>
					</p>
					<p>
						<code class="key"><?php echo esc_html( $log_path ); ?></code>
					</p>
					<?php if ( '' !== $tail ) : ?>
						<pre class="log-pre"><?php echo esc_html( $tail ); ?></pre>
					<?php else : ?>
						<p class="fhint"><?php esc_html_e( 'Noch keine Einträge – bitte einmal eine Karte im Frontend senden.', 'bs-kudo-karten' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Lokale Entwicklungs-URL erkennen.
	 *
	 * @return bool
	 */
	private static function is_local_site() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		$host = strtolower( $host );

		if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		$suffixes = array( '.local', '.test', '.localhost', '.invalid' );

		foreach ( $suffixes as $suffix ) {
			if ( str_ends_with( $host, $suffix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Bekannte SMTP-Plugins erkennen.
	 *
	 * @return string
	 */
	private static function detect_smtp_plugin() {
		if ( defined( 'WPMS_ON' ) || class_exists( 'WPMailSMTP\Core', false ) ) {
			return 'WP Mail SMTP';
		}

		if ( class_exists( 'PostmanWpMail', false ) ) {
			return 'Post SMTP';
		}

		if ( class_exists( 'FluentMail\App\App', false ) ) {
			return 'FluentSMTP';
		}

		return 'none (Standard PHP mail / sendmail)';
	}

	/**
	 * Debug-Verzeichnis anlegen und absichern.
	 */
	private static function ensure_dir() {
		$dir = self::get_dir();

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$index = $dir . 'index.php';
		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}

		$htaccess = $dir . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, "Deny from all\n" );
		}
	}
}
