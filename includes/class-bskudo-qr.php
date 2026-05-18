<?php
/**
 * QR-Code für E-Mails (Link zur Webansicht).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * QR-Bild als Base64-PNG für Inline-Einbindung in HTML-Mails.
 */
class BSKudo_QR {

	const DEFAULT_SIZE = 120;

	/**
	 * QR-Code als data-URI (PNG, Base64) für img src.
	 *
	 * @param string $url  Ziel-URL.
	 * @param int    $size Kantenlänge in Pixel.
	 * @return string Leer bei Fehler.
	 */
	public static function get_data_uri( $url, $size = self::DEFAULT_SIZE ) {
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return '';
		}

		$size = max( 80, min( 300, absint( $size ) ) );
		$png  = self::fetch_png( $url, $size );

		if ( '' === $png ) {
			return '';
		}

		return 'data:image/png;base64,' . base64_encode( $png );
	}

	/**
	 * PNG-Binary vom QR-Dienst laden (gecacht pro URL).
	 *
	 * @param string $url  Ziel-URL.
	 * @param int    $size Größe.
	 * @return string
	 */
	private static function fetch_png( $url, $size ) {
		$cache_key = 'bskudo_qr_' . md5( $url . '|' . $size );
		$cached    = get_transient( $cache_key );

		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$api_url = add_query_arg(
			array(
				'size'   => $size . 'x' . $size,
				'format' => 'png',
				'data'   => $url,
			),
			'https://api.qrserver.com/v1/create-qr-code/'
		);

		/**
		 * QR-API-URL anpassen (eigener Dienst möglich).
		 *
		 * @param string $api_url API-URL.
		 * @param string $url     Ziel-URL.
		 * @param int    $size    Größe.
		 */
		$api_url = apply_filters( 'bskudo_qr_api_url', $api_url, $url, $size );

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 12,
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return '';
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body || strlen( $body ) < 100 ) {
			return '';
		}

		set_transient( $cache_key, $body, DAY_IN_SECONDS );

		return $body;
	}
}
