<?php
/**
 * QR-Code für E-Mails und Webansicht (lokal generiert).
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
	 * PNG-Binary lokal erzeugen (gecacht pro URL).
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

		$png = self::generate_png( $url, $size );

		if ( '' === $png ) {
			return '';
		}

		set_transient( $cache_key, $png, DAY_IN_SECONDS );

		return $png;
	}

	/**
	 * PNG per chillerlan/php-qrcode erzeugen.
	 *
	 * @param string $url  Ziel-URL.
	 * @param int    $size Kantenlänge in Pixel.
	 * @return string
	 */
	private static function generate_png( $url, $size ) {
		if ( ! class_exists( '\chillerlan\QRCode\QRCode' ) ) {
			return '';
		}

		try {
			$options = new \chillerlan\QRCode\QROptions(
				array(
					'outputInterface'  => \chillerlan\QRCode\Output\QRGdImagePNG::class,
					'outputBase64'     => false,
					'scale'            => max( 1, (int) round( $size / 25 ) ),
					'imageTransparent' => false,
				)
			);

			$png = ( new \chillerlan\QRCode\QRCode( $options ) )->render( $url );

			if ( ! is_string( $png ) || strlen( $png ) < 100 ) {
				return '';
			}

			return $png;
		} catch ( Exception $e ) {
			return '';
		}
	}
}
