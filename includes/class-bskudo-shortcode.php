<?php
/**
 * Shortcode [kudo_karten] – öffentliche Kartenanzeige.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rendert den Wizard (Phase 3: alle Schritte, Live-Vorschau).
 */
class BSKudo_Shortcode {

	/**
	 * Shortcode-Tag.
	 */
	const TAG = 'kudo_karten';

	/**
	 * Standard-Zeichenlimit für Karten-Text.
	 */
	const CHAR_LIMIT = 160;

	/**
	 * Ob der Footer-Hook für verspätetes CSS bereits gesetzt wurde.
	 *
	 * @var bool
	 */
	private static $footer_styles_hooked = false;

	/**
	 * Ob Assets bereits in die Warteschlange gelegt wurden.
	 *
	 * @var bool
	 */
	private static $assets_enqueued = false;

	/**
	 * Aktueller Set-Filter des Shortcodes (für JS-Daten).
	 *
	 * @var string
	 */
	private $set_slug = '';

	/**
	 * Hooks registrieren.
	 */
	public function register() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Shortcode ausgeben.
	 *
	 * @param array<string, string>|string $atts Shortcode-Attribute.
	 * @return string HTML.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'set' => '',
			),
			$atts,
			self::TAG
		);

		$this->set_slug = $atts['set'];

		// Shortcode läuft nach wp_enqueue_scripts – Assets hier nachladen.
		$this->enqueue_assets();

		$cards          = $this->get_cards( $this->set_slug );
		$char_limit     = self::CHAR_LIMIT;
		$privacy_text   = $this->get_privacy_text();
		$branding_back  = $this->get_default_branding_text();

		ob_start();
		include BSKUDO_PATH . 'public/templates/wizard.php';
		return (string) ob_get_clean();
	}

	/**
	 * Styles und Scripts registrieren (ohne zu laden).
	 */
	public function register_assets() {
		wp_register_style(
			'bskudo-wizard',
			BSKUDO_URL . 'public/css/bskudo-wizard.css',
			array(),
			BSKUDO_VERSION
		);

		wp_register_script(
			'bskudo-wizard',
			BSKUDO_URL . 'public/js/bskudo-wizard.js',
			array(),
			BSKUDO_VERSION,
			true
		);
	}

	/**
	 * Assets vorab laden, wenn der Shortcode im Beitragsinhalt steht.
	 */
	public function maybe_enqueue_assets() {
		if ( $this->post_has_shortcode() ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * CSS und JS laden (idempotent).
	 */
	public function enqueue_assets() {
		if ( self::$assets_enqueued ) {
			$this->maybe_hook_footer_styles();
			return;
		}

		self::$assets_enqueued = true;

		if ( ! wp_style_is( 'bskudo-wizard', 'registered' ) ) {
			$this->register_assets();
		}

		wp_enqueue_style( 'bskudo-wizard' );
		wp_enqueue_script( 'bskudo-wizard' );

		wp_localize_script(
			'bskudo-wizard',
			'bskudoWizard',
			array(
				'maxChars'      => self::CHAR_LIMIT,
				'brandingBack'  => $this->get_default_branding_text(),
				'cards'         => $this->get_cards_for_js(),
				'textbausteine' => $this->get_textbausteine_for_js(),
				'i18n'          => array(
					'previewFront'     => __( 'Vorderseite', 'bs-kudo-karten' ),
					'previewBack'      => __( 'Rückseite (Branding)', 'bs-kudo-karten' ),
					'flipHint'         => __( 'Umdrehen zeigt das Branding auf der Rückseite.', 'bs-kudo-karten' ),
					'showCardBack'     => __( 'Rückseite anzeigen', 'bs-kudo-karten' ),
					'showCardFront'    => __( 'Vorderseite anzeigen', 'bs-kudo-karten' ),
					'selectCard'       => __( 'Bitte wähle eine Karte aus.', 'bs-kudo-karten' ),
					'cardSelected'     => __( 'Karte ausgewählt', 'bs-kudo-karten' ),
					'enterMessage'     => __( 'Bitte schreibe einen kurzen Text für deine Karte.', 'bs-kudo-karten' ),
					'charLimit'        => __( 'Maximal %d Zeichen.', 'bs-kudo-karten' ),
					'fillAllFields'    => __( 'Bitte fülle alle Pflichtfelder aus.', 'bs-kudo-karten' ),
					'invalidEmail'     => __( 'Bitte gib eine gültige E-Mail-Adresse ein.', 'bs-kudo-karten' ),
					'next'             => __( 'Weiter', 'bs-kudo-karten' ),
					'back'             => __( 'Zurück', 'bs-kudo-karten' ),
					'sendSoon'         => __( 'Versand wird in der nächsten Version freigeschaltet.', 'bs-kudo-karten' ),
					'noTextbausteine'  => __( 'Keine Textbausteine für diese Karte – schreib deinen eigenen Text.', 'bs-kudo-karten' ),
					'previewLabel'     => __( 'Live-Vorschau deiner Karte', 'bs-kudo-karten' ),
					'largePreview'     => __( 'Große Vorschau', 'bs-kudo-karten' ),
					'impulsesLabel'    => __( 'Textimpulse', 'bs-kudo-karten' ),
				),
			)
		);

		$this->maybe_hook_footer_styles();
	}

	/**
	 * Footer-Ausgabe für CSS anmelden, wenn wp_head bereits vorbei ist.
	 */
	private function maybe_hook_footer_styles() {
		if ( did_action( 'wp_head' ) && ! self::$footer_styles_hooked ) {
			self::$footer_styles_hooked = true;
			add_action( 'wp_footer', array( $this, 'print_footer_styles' ), 5 );
		}
	}

	/**
	 * Styles im Footer ausgeben, wenn sie im Head nicht mehr geladen werden konnten.
	 */
	public function print_footer_styles() {
		if ( wp_style_is( 'bskudo-wizard', 'enqueued' ) && ! wp_style_is( 'bskudo-wizard', 'done' ) ) {
			wp_print_styles( array( 'bskudo-wizard' ) );
		}
	}

	/**
	 * Prüfen, ob der aktuelle Beitrag den Shortcode enthält.
	 *
	 * @return bool
	 */
	private function post_has_shortcode() {
		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return has_shortcode( $post->post_content, self::TAG );
	}

	/**
	 * Veröffentlichte Kudo-Karten laden.
	 *
	 * @param string $set_slug Optional: Kudo-Set (Taxonomie-Slug) filtern.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_cards( $set_slug = '' ) {
		$query_args = array(
			'post_type'      => 'kudo_card',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		);

		if ( '' !== $set_slug ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'kudo_set',
					'field'    => 'slug',
					'terms'    => sanitize_title( $set_slug ),
				),
			);
		}

		$query = new WP_Query( $query_args );
		$cards = array();

		if ( ! $query->have_posts() ) {
			return $cards;
		}

		foreach ( $query->posts as $post ) {
			$cards[] = $this->map_card( $post );
		}

		wp_reset_postdata();

		return $cards;
	}

	/**
	 * Post-Daten für das Template aufbereiten.
	 *
	 * @param WP_Post $post Kudo-Karten-Post.
	 * @return array<string, mixed>
	 */
	private function map_card( WP_Post $post ) {
		$image_id    = get_post_thumbnail_id( $post->ID );
		$image_width = 0;
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
		$impulse  = get_post_meta( $post->ID, '_bskudo_impulse_text', true );

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

		$set_ids = wp_get_post_terms( $post->ID, 'kudo_set', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $set_ids ) ) {
			$set_ids = array();
		}

		return array(
			'id'            => $post->ID,
			'title'         => get_the_title( $post ),
			'image_url'     => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'medium_large' ) : '',
			'image_width'   => $image_width,
			'image_height'  => $image_height,
			'image_alt'     => $image_id ? (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '',
			'impulse'        => is_string( $impulse ) ? $impulse : '',
			'back_branding'  => is_string( $impulse ) ? trim( $impulse ) : '',
			'accent_color'   => $accent,
			'icon_position'  => $icon_pos,
			'set_ids'        => array_map( 'intval', $set_ids ),
		);
	}

	/**
	 * Karten-Daten für JavaScript (öffentliche Felder).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_cards_for_js() {
		$cards   = $this->get_cards( $this->set_slug );
		$payload = array();

		foreach ( $cards as $card ) {
			$payload[] = array(
				'id'            => (int) $card['id'],
				'title'         => (string) $card['title'],
				'imageUrl'      => (string) $card['image_url'],
				'imageWidth'    => (int) ( $card['image_width'] ?? 0 ),
				'imageHeight'   => (int) ( $card['image_height'] ?? 0 ),
				'imageAlt'      => (string) $card['image_alt'],
				'accentColor'   => (string) $card['accent_color'],
				'iconPosition'  => (string) $card['icon_position'],
				'backBranding'  => (string) ( $card['back_branding'] ?? '' ),
				'setIds'        => isset( $card['set_ids'] ) ? $card['set_ids'] : array(),
			);
		}

		return $payload;
	}

	/**
	 * Textbausteine für JavaScript laden.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_textbausteine_for_js() {
		$query = new WP_Query(
			array(
				'post_type'      => 'kudo_textbaustein',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$items = array();

		if ( ! $query->have_posts() ) {
			return $items;
		}

		foreach ( $query->posts as $post ) {
			$text = wp_strip_all_tags( $post->post_content );
			if ( function_exists( 'mb_substr' ) ) {
				$text = mb_substr( $text, 0, self::CHAR_LIMIT );
			} else {
				$text = substr( $text, 0, self::CHAR_LIMIT );
			}

			$text = trim( $text );
			if ( '' === $text ) {
				continue;
			}

			$card_id = (int) get_post_meta( $post->ID, '_bskudo_linked_card', true );
			$set_ids = get_post_meta( $post->ID, '_bskudo_linked_sets', true );

			if ( ! is_array( $set_ids ) ) {
				$set_ids = array();
			}

			$items[] = array(
				'id'     => $post->ID,
				'text'   => $text,
				'cardId' => $card_id,
				'setIds' => array_map( 'intval', $set_ids ),
			);
		}

		wp_reset_postdata();

		return $items;
	}

	/**
	 * Datenschutzhinweis (Standard bis Backend-Einstellungen in Phase 6).
	 *
	 * @return string
	 */
	private function get_privacy_text() {
		return __( 'Deine Angaben werden nur zum Versand dieser Kudo-Karte verwendet und nicht gespeichert.', 'bs-kudo-karten' );
	}

	/**
	 * Standard-Text für die Karten-Rückseite (Branding-Tab in späterer Phase).
	 *
	 * @return string
	 */
	private function get_default_branding_text() {
		$default = __( 'Mit Herz · Systemische Beratung', 'bs-kudo-karten' );

		/**
		 * Standard-Branding auf der Karten-Rückseite anpassen.
		 *
		 * @param string $text Branding-Text.
		 */
		return (string) apply_filters( 'bskudo_default_branding_text', $default );
	}
}
