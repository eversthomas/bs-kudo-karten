<?php
/**
 * Serverseitiges JPG-Rendering für E-Mails (Vorder- und Rückseite).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Erzeugt fertige Karten-JPGs per GD (Text eingebrannt, Branding auf der Rückseite).
 */
class BSKudo_Card_Renderer {

	const MAIL_WIDTH = 520;

	const JPEG_QUALITY = 88;

	/**
	 * Vorder- und Rückseite als Base64-JPEG für die Mail.
	 *
	 * @param int    $card_id       Kudo-Karten-Post-ID.
	 * @param string $message       Nutzer-Text.
	 * @param string $branding_text Rückseiten-Branding.
	 * @return array{front: string, back: string}|null Base64 ohne data-URI-Prefix, null bei Fehler.
	 */
	public static function render_for_mail( $card_id, $message, $branding_text ) {
		if ( ! self::is_gd_available() ) {
			return null;
		}

		$card_id = absint( $card_id );
		$message = trim( (string) $message );

		if ( $card_id < 1 || '' === $message ) {
			return null;
		}

		$context = self::get_card_context( $card_id, $branding_text );
		if ( null === $context ) {
			return null;
		}

		$front = self::render_front( $context, $message );
		$back  = self::render_back( $context );

		if ( ! $front || ! $back ) {
			if ( $front ) {
				imagedestroy( $front );
			}
			if ( $back ) {
				imagedestroy( $back );
			}
			if ( ! empty( $context['logo'] ) ) {
				imagedestroy( $context['logo'] );
			}
			return null;
		}

		$front_b64 = self::image_to_base64( $front );
		$back_b64  = self::image_to_base64( $back );

		imagedestroy( $front );
		imagedestroy( $back );
		if ( ! empty( $context['logo'] ) ) {
			imagedestroy( $context['logo'] );
		}

		if ( '' === $front_b64 || '' === $back_b64 ) {
			return null;
		}

		return array(
			'front' => $front_b64,
			'back'  => $back_b64,
		);
	}

	/**
	 * Prüfen, ob GD mit JPEG-Unterstützung verfügbar ist.
	 *
	 * @return bool
	 */
	public static function is_gd_available() {
		return function_exists( 'imagecreatetruecolor' )
			&& function_exists( 'imagejpeg' )
			&& ( function_exists( 'imagecreatefromjpeg' ) || function_exists( 'imagecreatefromstring' ) );
	}

	/**
	 * Karten-Kontext für Rendering laden.
	 *
	 * @param int    $card_id       Post-ID.
	 * @param string $branding_text Branding-Text.
	 * @return array<string, mixed>|null
	 */
	private static function get_card_context( $card_id, $branding_text ) {
		$post = get_post( $card_id );

		if ( ! $post instanceof WP_Post || 'kudo_card' !== $post->post_type ) {
			return null;
		}

		$image_id = get_post_thumbnail_id( $card_id );
		$source   = $image_id ? self::load_image_from_attachment( $image_id ) : null;

		$accent = get_post_meta( $card_id, '_bskudo_accent_color', true );
		if ( ! is_string( $accent ) || '' === $accent ) {
			$accent = (string) BSKudo_Settings::get( 'branding', 'primary_color', '#c45c3e' );
		}
		$accent = sanitize_hex_color( $accent );
		if ( ! $accent ) {
			$accent = '#c45c3e';
		}

		$icon_pos = get_post_meta( $card_id, '_bskudo_icon_position', true );
		if ( ! in_array( $icon_pos, array( 'left', 'center', 'right' ), true ) ) {
			$icon_pos = 'center';
		}

		$logo_id  = (int) BSKudo_Settings::get( 'branding', 'logo_id', 0 );
		$logo_img = $logo_id ? self::load_image_from_attachment( $logo_id ) : null;

		$branding_text = trim( (string) $branding_text );
		if ( '' === $branding_text ) {
			$branding_text = (string) BSKudo_Settings::get( 'branding', 'branding_text', '' );
		}

		$width  = self::MAIL_WIDTH;
		$height = (int) round( $width * 1.333 );

		if ( $source ) {
			$width  = imagesx( $source );
			$height = imagesy( $source );
			$scaled = self::resize_image( $source, self::MAIL_WIDTH );
			imagedestroy( $source );
			$source = $scaled;
			if ( $source ) {
				$width  = imagesx( $source );
				$height = imagesy( $source );
			}
		}

		return array(
			'title'         => get_the_title( $post ),
			'source'        => $source,
			'width'         => $width,
			'height'        => $height,
			'accent'        => $accent,
			'accent_rgb'    => self::hex_to_rgb( $accent ),
			'icon_position' => $icon_pos,
			'branding_text' => $branding_text,
			'logo'          => $logo_img,
		);
	}

	/**
	 * Vorderseite: Kartenbild + Nutzertext.
	 *
	 * @param array<string, mixed> $context Kontext.
	 * @param string               $message Text.
	 * @return GdImage|false
	 */
	private static function render_front( $context, $message ) {
		$w = (int) $context['width'];
		$h = (int) $context['height'];

		if ( $w < 1 || $h < 1 ) {
			return false;
		}

		if ( $context['source'] ) {
			$canvas = self::clone_image( $context['source'], $w, $h );
		} else {
			$canvas = imagecreatetruecolor( $w, $h );
			$bg     = imagecolorallocate( $canvas, 250, 248, 246 );
			imagefill( $canvas, 0, 0, $bg );
			self::draw_placeholder_title( $canvas, (string) $context['title'], $w, $h );
		}

		if ( ! $canvas ) {
			return false;
		}

		self::draw_message_on_card( $canvas, $message, (string) $context['icon_position'], $w, $h );

		return $canvas;
	}

	/**
	 * Rückseite: Branding mit Akzentfarbe und optionalem Logo.
	 *
	 * @param array<string, mixed> $context Kontext.
	 * @return GdImage|false
	 */
	private static function render_back( $context ) {
		$w = (int) $context['width'];
		$h = (int) $context['height'];

		$canvas = imagecreatetruecolor( $w, $h );
		$rgb    = $context['accent_rgb'];
		$bg     = imagecolorallocate( $canvas, $rgb['r'], $rgb['g'], $rgb['b'] );
		imagefill( $canvas, 0, 0, $bg );

		$light = imagecolorallocate(
			$canvas,
			min( 255, $rgb['r'] + 40 ),
			min( 255, $rgb['g'] + 40 ),
			min( 255, $rgb['b'] + 40 )
		);

		$y_offset = (int) ( $h * 0.12 );

		if ( $context['logo'] ) {
			$logo_max_w = (int) ( $w * 0.45 );
			$logo       = self::resize_image( $context['logo'], $logo_max_w );
			if ( $logo ) {
				$lw = imagesx( $logo );
				$lh = imagesy( $logo );
				$lx = (int) ( ( $w - $lw ) / 2 );
				imagecopy( $canvas, $logo, $lx, $y_offset, 0, 0, $lw, $lh );
				$y_offset += $lh + (int) ( $h * 0.06 );
				imagedestroy( $logo );
			}
		}

		$text_color = imagecolorallocate( $canvas, 255, 255, 255 );
		$lines      = self::wrap_text( (string) $context['branding_text'], 28 );
		$font_size  = 18;
		$line_h     = (int) ( $font_size * 1.45 );
		$total_h    = count( $lines ) * $line_h;
		$start_y    = (int) ( ( $h - $total_h ) / 2 );

		if ( $start_y < $y_offset ) {
			$start_y = $y_offset;
		}

		foreach ( $lines as $i => $line ) {
			self::draw_text_line( $canvas, $line, $font_size, (int) ( $w / 2 ), $start_y + ( $i * $line_h ), 'center', $text_color );
		}

		// Dezente Verzierung unten.
		imagefilledellipse( $canvas, (int) ( $w * 0.2 ), (int) ( $h * 0.88 ), 40, 40, $light );
		imagefilledellipse( $canvas, (int) ( $w * 0.8 ), (int) ( $h * 0.92 ), 28, 28, $light );

		return $canvas;
	}

	/**
	 * Nutzertext im unteren Kartenbereich zeichnen.
	 *
	 * @param GdImage $canvas       Bild.
	 * @param string  $message      Text.
	 * @param string  $icon_position left|center|right.
	 * @param int     $w            Breite.
	 * @param int     $h            Höhe.
	 */
	private static function draw_message_on_card( $canvas, $message, $icon_position, $w, $h ) {
		$zone_top    = (int) ( $h * 0.58 );
		$zone_bottom = (int) ( $h * 0.94 );
		$zone_h      = $zone_bottom - $zone_top;
		$padding_x   = (int) ( $w * 0.08 );
		$max_w       = $w - ( 2 * $padding_x );

		$shadow = imagecolorallocatealpha( $canvas, 0, 0, 0, 70 );
		$white  = imagecolorallocate( $canvas, 255, 255, 255 );

		// Halbtransparenter Hintergrund für Lesbarkeit.
		$overlay = imagecolorallocatealpha( $canvas, 255, 255, 255, 90 );
		imagefilledrectangle( $canvas, $padding_x, $zone_top, $w - $padding_x, $zone_bottom, $overlay );

		$font_size = 14;
		$best_size = 10;

		for ( $size = 10; $size <= 36; $size++ ) {
			$lines = self::wrap_text( $message, self::chars_per_line( $max_w, $size ) );
			if ( count( $lines ) > 8 ) {
				break;
			}
			$line_h = (int) ( $size * 1.35 );
			if ( count( $lines ) * $line_h <= $zone_h ) {
				$best_size = $size;
			} else {
				break;
			}
		}

		$lines   = self::wrap_text( $message, self::chars_per_line( $max_w, $best_size ) );
		$lines   = array_slice( $lines, 0, 8 );
		$line_h  = (int) ( $best_size * 1.35 );
		$total_h = count( $lines ) * $line_h;
		$start_y = $zone_top + (int) ( ( $zone_h - $total_h ) / 2 ) + $best_size;

		$align_map = array(
			'left'   => 'left',
			'right'  => 'right',
			'center' => 'center',
		);
		$align = isset( $align_map[ $icon_position ] ) ? $align_map[ $icon_position ] : 'center';

		$x_pos = (int) ( $w / 2 );
		if ( 'left' === $align ) {
			$x_pos = $padding_x;
		} elseif ( 'right' === $align ) {
			$x_pos = $w - $padding_x;
		}

		foreach ( $lines as $i => $line ) {
			$y = $start_y + ( $i * $line_h );
			self::draw_text_line( $canvas, $line, $best_size, $x_pos, $y, $align, $shadow );
			self::draw_text_line( $canvas, $line, $best_size, $x_pos - 1, $y - 1, $align, $white );
		}
	}

	/**
	 * Platzhalter-Titel wenn kein Bild vorhanden.
	 *
	 * @param GdImage $canvas Bild.
	 * @param string  $title  Titel.
	 * @param int     $w      Breite.
	 * @param int     $h      Höhe.
	 */
	private static function draw_placeholder_title( $canvas, $title, $w, $h ) {
		$color = imagecolorallocate( $canvas, 120, 120, 120 );
		self::draw_text_line( $canvas, $title, 20, (int) ( $w / 2 ), (int) ( $h * 0.4 ), 'center', $color );
	}

	/**
	 * Eine Textzeile mit TTF oder Fallback zeichnen.
	 *
	 * @param GdImage $canvas Bild.
	 * @param string  $text   Text.
	 * @param int     $size   Schriftgröße.
	 * @param int     $x      X.
	 * @param int     $y      Y (Baseline).
	 * @param string  $align  left|center|right.
	 * @param int     $color  Farbindex.
	 */
	private static function draw_text_line( $canvas, $text, $size, $x, $y, $align, $color ) {
		$font = self::get_font_path();

		if ( $font && function_exists( 'imagettftext' ) ) {
			$box  = imagettfbbox( $size, 0, $font, $text );
			$tw   = abs( $box[2] - $box[0] );
			$draw_x = $x;

			if ( 'center' === $align ) {
				$draw_x = (int) ( $x - ( $tw / 2 ) );
			} elseif ( 'right' === $align ) {
				$draw_x = (int) ( $x - $tw );
			}

			imagettftext( $canvas, $size, 0, $draw_x, $y, $color, $font, $text );
			return;
		}

		$font_h = 14;
		$draw_x = $x;
		if ( 'center' === $align ) {
			$draw_x = (int) ( $x - ( strlen( $text ) * 4 ) );
		}
		imagestring( $canvas, 3, max( 4, $draw_x ), $y - $font_h, $text, $color );
	}

	/**
	 * Zeichen pro Zeile (Näherung für Umbruch).
	 *
	 * @param int $max_w    Max. Breite px.
	 * @param int $font_size Schriftgröße.
	 * @return int
	 */
	private static function chars_per_line( $max_w, $font_size ) {
		$per_char = max( 6, (int) ( $font_size * 0.55 ) );

		return max( 8, (int) floor( $max_w / $per_char ) );
	}

	/**
	 * Text in Zeilen umbrechen (Wortgrenzen).
	 *
	 * @param string $text      Text.
	 * @param int    $max_chars Zeichen pro Zeile.
	 * @return array<int, string>
	 */
	private static function wrap_text( $text, $max_chars ) {
		$text = preg_replace( '/\s+/u', ' ', trim( $text ) );
		if ( '' === $text ) {
			return array();
		}

		$words  = preg_split( '/\s+/u', $text );
		$lines  = array();
		$current = '';

		foreach ( $words as $word ) {
			$candidate = '' === $current ? $word : $current . ' ' . $word;
			if ( mb_strlen( $candidate ) <= $max_chars ) {
				$current = $candidate;
			} else {
				if ( '' !== $current ) {
					$lines[] = $current;
				}
				$current = mb_strlen( $word ) > $max_chars ? mb_substr( $word, 0, $max_chars ) : $word;
			}
		}

		if ( '' !== $current ) {
			$lines[] = $current;
		}

		return $lines;
	}

	/**
	 * Bild auf Zielbreite skalieren.
	 *
	 * @param GdImage $source    Quelle.
	 * @param int     $max_width Max. Breite.
	 * @return GdImage|false
	 */
	private static function resize_image( $source, $max_width ) {
		$sw = imagesx( $source );
		$sh = imagesy( $source );

		if ( $sw <= $max_width ) {
			return self::clone_image( $source, $sw, $sh );
		}

		$dw = $max_width;
		$dh = (int) round( $sh * ( $max_width / $sw ) );

		$dest = imagecreatetruecolor( $dw, $dh );
		imagecopyresampled( $dest, $source, 0, 0, 0, 0, $dw, $dh, $sw, $sh );

		return $dest;
	}

	/**
	 * Bild kopieren.
	 *
	 * @param GdImage $source Quelle.
	 * @param int     $w      Breite.
	 * @param int     $h      Höhe.
	 * @return GdImage|false
	 */
	private static function clone_image( $source, $w, $h ) {
		$dest = imagecreatetruecolor( $w, $h );
		imagecopy( $dest, $source, 0, 0, 0, 0, $w, $h );

		return $dest;
	}

	/**
	 * Attachment als GD-Bild laden.
	 *
	 * @param int $attachment_id Medien-ID.
	 * @return GdImage|false
	 */
	private static function load_image_from_attachment( $attachment_id ) {
		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! file_exists( $path ) ) {
			return false;
		}

		$mime = get_post_mime_type( $attachment_id );

		switch ( $mime ) {
			case 'image/jpeg':
			case 'image/jpg':
				return imagecreatefromjpeg( $path );
			case 'image/png':
				$img = imagecreatefrompng( $path );
				if ( $img ) {
					imagealphablending( $img, true );
					imagesavealpha( $img, true );
				}
				return $img;
			case 'image/gif':
				return imagecreatefromgif( $path );
			case 'image/webp':
				if ( function_exists( 'imagecreatefromwebp' ) ) {
					return imagecreatefromwebp( $path );
				}
				break;
		}

		// Fallback: WordPress skaliert ggf. automatisch.
		$data = file_get_contents( $path );
		if ( $data ) {
			return imagecreatefromstring( $data );
		}

		return false;
	}

	/**
	 * GD-Bild als JPEG-Base64.
	 *
	 * @param GdImage $image Bild.
	 * @return string
	 */
	private static function image_to_base64( $image ) {
		ob_start();
		imagejpeg( $image, null, self::JPEG_QUALITY );
		$data = (string) ob_get_clean();

		return '' !== $data ? base64_encode( $data ) : '';
	}

	/**
	 * Hex-Farbe in RGB.
	 *
	 * @param string $hex Hex (#rrggbb).
	 * @return array{r: int, g: int, b: int}
	 */
	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array(
			'r' => hexdec( substr( $hex, 0, 2 ) ),
			'g' => hexdec( substr( $hex, 2, 2 ) ),
			'b' => hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * TTF-Schriftpfad ermitteln.
	 *
	 * @return string|false
	 */
	private static function get_font_path() {
		static $font = null;

		if ( null !== $font ) {
			return $font ? $font : false;
		}

		$candidates = array(
			BSKUDO_PATH . 'assets/fonts/DejaVuSans.ttf',
			'/System/Library/Fonts/Supplemental/Arial.ttf',
			'/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
			'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
			'/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
			'C:\\Windows\\Fonts\\arial.ttf',
		);

		$glob = glob( BSKUDO_PATH . 'assets/fonts/*.ttf' );
		if ( is_array( $glob ) ) {
			$candidates = array_merge( $glob, $candidates );
		}

		foreach ( $candidates as $path ) {
			if ( is_string( $path ) && file_exists( $path ) ) {
				$font = $path;
				return $font;
			}
		}

		$font = false;
		return false;
	}
}
