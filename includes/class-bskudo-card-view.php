<?php
/**
 * Öffentliche Webansicht für Empfänger (tokenbasiert).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite-Regel, Template und Assets für card-view.php.
 */
class BSKudo_Card_View {

	/**
	 * Hooks registrieren.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	/**
	 * Pretty-Permalink für /kudo-karte/{token}/.
	 */
	public function register_rewrite() {
		add_rewrite_rule(
			'^' . BSKudo_Token::REWRITE_SLUG . '/([^/]+)/?$',
			'index.php?' . BSKudo_Token::QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Query-Var registrieren.
	 *
	 * @param array<int, string> $vars Query-Vars.
	 * @return array<int, string>
	 */
	public function add_query_vars( $vars ) {
		$vars[] = BSKudo_Token::QUERY_VAR;

		return $vars;
	}

	/**
	 * Bei gültigem Token die Webansicht ausgeben.
	 */
	public function maybe_render() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Öffentlicher Token-Endpunkt – der Token selbst autorisiert
		// den Zugriff. Ein Nonce ist hier konzeptionell nicht anwendbar.
		$token = get_query_var( BSKudo_Token::QUERY_VAR );

		if ( '' === $token ) {
			if ( isset( $_GET[ BSKudo_Token::QUERY_VAR ] ) ) {
				$token = sanitize_text_field( wp_unslash( $_GET[ BSKudo_Token::QUERY_VAR ] ) );
			}
		}

		if ( '' === $token ) {
			return;
		}

		$payload = BSKudo_Token::resolve( $token );

		if ( null === $payload ) {
			$this->render_error_page(
				__( 'Diese Kudo-Karte ist nicht mehr verfügbar', 'bs-kudo-karten' ),
				__( 'Der Link ist abgelaufen oder ungültig.', 'bs-kudo-karten' ),
				410
			);
			exit;
		}

		$card = $this->get_card_data( (int) $payload['card_id'] );

		if ( null === $card ) {
			$this->render_error_page(
				__( 'Karte nicht gefunden', 'bs-kudo-karten' ),
				__( 'Die zugehörige Kudo-Karte existiert nicht mehr.', 'bs-kudo-karten' ),
				404
			);
			exit;
		}

		$sender_name = isset( $payload['sender_name'] ) ? (string) $payload['sender_name'] : '';

		$this->render_card_page( $card, (string) $payload['message'], $sender_name );
		exit;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Karten-Daten für die Webansicht laden.
	 *
	 * @param int $card_id Post-ID.
	 * @return array<string, mixed>|null
	 */
	public function get_card_data( $card_id ) {
		$post = get_post( absint( $card_id ) );

		if ( ! $post instanceof WP_Post || 'kudo_card' !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		$image_id     = get_post_thumbnail_id( $post->ID );
		$image_width  = 0;
		$image_height = 0;

		if ( $image_id ) {
			$image_meta = wp_get_attachment_metadata( $image_id );
			if ( is_array( $image_meta ) ) {
				$image_width  = isset( $image_meta['width'] ) ? (int) $image_meta['width'] : 0;
				$image_height = isset( $image_meta['height'] ) ? (int) $image_meta['height'] : 0;
			}
		}

		$accent   = get_post_meta( $post->ID, '_bskudo_accent_color', true );
		$icon_pos = get_post_meta( $post->ID, '_bskudo_icon_position', true );
		$back_branding_meta = get_post_meta( $post->ID, '_bskudo_back_branding', true );

		if ( ! is_string( $accent ) || '' === $accent ) {
			$accent = '#c45c3e';
		}

		$accent = sanitize_hex_color( $accent );
		if ( ! $accent ) {
			$accent = '#c45c3e';
		}

		$icon_positions = array( 'left', 'center', 'right' );
		if ( ! in_array( $icon_pos, $icon_positions, true ) ) {
			$icon_pos = 'center';
		}

		$col1 = get_post_meta( $post->ID, '_bskudo_back_branding_col1', true );
		$col2 = get_post_meta( $post->ID, '_bskudo_back_branding_col2', true );

		$branding_col1 = is_string( $col1 ) ? trim( $col1 ) : '';
		$branding_col2 = is_string( $col2 ) ? trim( $col2 ) : '';

		if ( '' === $branding_col1 ) {
			$branding_col1 = (string) BSKudo_Settings::get( 'branding', 'branding_text_col1', '' );
		}

		if ( '' === $branding_col2 ) {
			$branding_col2 = (string) BSKudo_Settings::get( 'branding', 'branding_text_col2', '' );
			if ( '' === $branding_col2 ) {
				$branding_col2 = is_string( $back_branding_meta ) ? trim( $back_branding_meta ) : '';
				if ( '' === $branding_col2 ) {
					$legacy = get_post_meta( $post->ID, '_bskudo_impulse_text', true );
					$branding_col2 = is_string( $legacy ) ? trim( $legacy ) : '';
				}
				if ( '' === $branding_col2 ) {
					$branding_col2 = (string) BSKudo_Settings::get( 'branding', 'branding_text', '' );
				}
			}
		}

		return array(
			'id'                 => $post->ID,
			'title'              => get_the_title( $post ),
			'image_url'          => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'large' ) : '',
			'image_width'        => $image_width,
			'image_height'       => $image_height,
			'image_alt'          => $image_id ? (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '',
			'back_branding_col1' => $branding_col1,
			'back_branding_col2' => $branding_col2,
			'accent_color'       => $accent,
			'icon_position'      => $icon_pos,
		);
	}

	/**
	 * Webansicht rendern.
	 *
	 * @param array<string, mixed> $card        Karten-Daten.
	 * @param string               $message     Nutzer-Text.
	 * @param string               $sender_name Name des Versenders.
	 */
	private function render_card_page( $card, $message, $sender_name = '' ) {
		$this->enqueue_assets( $card );

		status_header( 200 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );

		include BSKUDO_PATH . 'public/templates/card-view.php';
	}

	/**
	 * Fehlerseite (abgelaufen / nicht gefunden).
	 *
	 * @param string $title   Überschrift.
	 * @param string $detail  Beschreibung.
	 * @param int    $status  HTTP-Status.
	 */
	private function render_error_page( $title, $detail, $status ) {
		status_header( $status );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta name="robots" content="noindex,nofollow">
			<title><?php echo esc_html( $title ); ?></title>
			<style>
				body { font-family: system-ui, sans-serif; background: #f4f4f4; margin: 0; padding: 2rem; text-align: center; color: #333; }
				.bskudo-card-view-error { max-width: 420px; margin: 4rem auto; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
				h1 { font-size: 1.25rem; margin: 0 0 .75rem; }
				p { margin: 0; color: #666; line-height: 1.5; }
			</style>
		</head>
		<body>
			<div class="bskudo-card-view-error">
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo esc_html( $detail ); ?></p>
			</div>
		</body>
		</html>
		<?php
	}

	/**
	 * Styles und Scripts für die Webansicht laden.
	 *
	 * @param array<string, mixed> $card Karten-Daten.
	 */
	private function enqueue_assets( $card ) {
		wp_enqueue_style(
			'bskudo-wizard',
			BSKUDO_URL . 'public/css/bskudo-wizard.css',
			array(),
			BSKUDO_VERSION
		);

		wp_enqueue_style(
			'bskudo-card-view',
			BSKUDO_URL . 'public/css/bskudo-card-view.css',
			array( 'bskudo-wizard' ),
			BSKUDO_VERSION
		);

		wp_enqueue_script(
			'bskudo-message-fit',
			BSKUDO_URL . 'public/js/bskudo-message-fit.js',
			array(),
			BSKUDO_VERSION,
			true
		);

		wp_enqueue_script(
			'bskudo-card-view',
			BSKUDO_URL . 'public/js/bskudo-card-view.js',
			array( 'bskudo-message-fit' ),
			BSKUDO_VERSION,
			true
		);

		wp_localize_script(
			'bskudo-card-view',
			'bskudoCardView',
			array(
				'accentColor'  => (string) $card['accent_color'],
				'imageWidth'   => (int) $card['image_width'],
				'imageHeight'  => (int) $card['image_height'],
				'i18n'         => array(
					'showBack'  => __( 'Rückseite anzeigen', 'bs-kudo-karten' ),
					'showFront' => __( 'Vorderseite anzeigen', 'bs-kudo-karten' ),
				),
			)
		);

		wp_print_styles( array( 'bskudo-wizard', 'bskudo-card-view' ) );
		wp_print_scripts( array( 'bskudo-message-fit', 'bskudo-card-view' ) );
	}
}
