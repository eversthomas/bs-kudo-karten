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
	 * Gecachte Karten pro Set-Slug (Instance-Cache).
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private $cards_cache = array();

	/**
	 * Gecachte Textbausteine für JavaScript.
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private $textbausteine_cache = null;

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
		$this->localize_wizard_script();

		$cards               = $this->get_cards( $this->set_slug );
		$char_limit          = BSKudo_Settings::get_char_limit();
		$privacy_text        = $this->get_privacy_text();
		$branding_back       = $this->get_default_branding_text();
		$enable_send_to_self = BSKudo_Settings::is_feature_enabled( 'enable_send_to_self' );
		$enable_delayed_send = BSKudo_Settings::is_feature_enabled( 'enable_delayed_send' );

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
			'bskudo-message-fit',
			BSKUDO_URL . 'public/js/bskudo-message-fit.js',
			array(),
			BSKUDO_VERSION,
			true
		);

		wp_register_script(
			'bskudo-wizard',
			BSKUDO_URL . 'public/js/bskudo-wizard.js',
			array( 'bskudo-message-fit' ),
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
	 * CSS und JS laden (idempotent, ohne wp_localize_script).
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
		wp_enqueue_script( 'bskudo-message-fit' );
		wp_enqueue_script( 'bskudo-wizard' );

		$this->maybe_hook_footer_styles();
	}

	/**
	 * Wizard-Daten für JavaScript (nur aus render() aufrufen).
	 */
	private function localize_wizard_script() {
		if ( ! wp_script_is( 'bskudo-wizard', 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			'bskudo-wizard',
			'bskudoWizard',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'ajaxAction'         => BSKudo_Send::AJAX_ACTION,
				'nonce'              => wp_create_nonce( BSKudo_Security::NONCE_ACTION ),
				'maxChars'           => BSKudo_Settings::get_char_limit(),
				'brandingBack'       => $this->get_default_branding_text(),
				'enableSendToSelf'   => BSKudo_Settings::is_feature_enabled( 'enable_send_to_self' ),
				'enableDelayedSend'  => BSKudo_Settings::is_feature_enabled( 'enable_delayed_send' ),
				'scheduleMinMinutes' => 5,
				'cards'              => $this->get_cards_for_js(),
				'textbausteine'      => $this->get_textbausteine_for_js(),
				'i18n'               => array(
					'previewFront'    => __( 'Vorderseite', 'bs-kudo-karten' ),
					'previewBack'     => __( 'Rückseite (Branding)', 'bs-kudo-karten' ),
					'flipHint'        => __( 'Umdrehen zeigt das Branding auf der Rückseite.', 'bs-kudo-karten' ),
					'showCardBack'    => __( 'Rückseite anzeigen', 'bs-kudo-karten' ),
					'showCardFront'   => __( 'Vorderseite anzeigen', 'bs-kudo-karten' ),
					'selectCard'      => __( 'Bitte wähle eine Karte aus.', 'bs-kudo-karten' ),
					'cardSelected'    => __( 'Karte ausgewählt', 'bs-kudo-karten' ),
					'enterMessage'    => __( 'Bitte schreibe einen kurzen Text für deine Karte.', 'bs-kudo-karten' ),
					/* translators: %d: maximum number of characters allowed */
					'charLimit'       => __( 'Maximal %d Zeichen.', 'bs-kudo-karten' ),
					'fillAllFields'   => __( 'Bitte fülle alle Pflichtfelder aus.', 'bs-kudo-karten' ),
					'invalidEmail'    => __( 'Bitte gib eine gültige E-Mail-Adresse ein.', 'bs-kudo-karten' ),
					'next'            => __( 'Weiter', 'bs-kudo-karten' ),
					'back'            => __( 'Zurück', 'bs-kudo-karten' ),
					'send'            => __( 'Kudo-Karte senden', 'bs-kudo-karten' ),
					'sending'         => __( 'Wird gesendet …', 'bs-kudo-karten' ),
					'sendError'       => __( 'Beim Versand ist ein Fehler aufgetreten. Bitte versuche es erneut.', 'bs-kudo-karten' ),
					'sendAnother'     => __( 'Weitere Kudo-Karte senden', 'bs-kudo-karten' ),
					'sendToSelf'      => __( 'Karte an mich selbst senden', 'bs-kudo-karten' ),
					'scheduleLater'   => __( 'Später senden', 'bs-kudo-karten' ),
					'scheduleInvalid' => __( 'Bitte wähle ein gültiges Datum in der Zukunft (mindestens 5 Minuten).', 'bs-kudo-karten' ),
					'noTextbausteine' => __( 'Keine Textbausteine für diese Karte – schreib deinen eigenen Text.', 'bs-kudo-karten' ),
					'previewLabel'    => __( 'Live-Vorschau deiner Karte', 'bs-kudo-karten' ),
					'largePreview'    => __( 'Große Vorschau', 'bs-kudo-karten' ),
					'impulsesLabel'   => __( 'Textimpulse', 'bs-kudo-karten' ),
				),
			)
		);
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
	 * Veröffentlichte Kudo-Karten laden (mit Instance-Cache).
	 *
	 * @param string $set_slug Optional: Kudo-Set (Taxonomie-Slug) filtern.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_cards( $set_slug = '' ) {
		$cache_key = $set_slug;

		if ( isset( $this->cards_cache[ $cache_key ] ) ) {
			return $this->cards_cache[ $cache_key ];
		}

		$query_args = array(
			'post_type'      => 'kudo_card',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		);

		if ( '' !== $set_slug ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
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

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$cards[] = $this->map_card( $post );
			}
		}

		wp_reset_postdata();

		$this->cards_cache[ $cache_key ] = $cards;

		return $cards;
	}

	/**
	 * Post-Daten für das Template aufbereiten.
	 *
	 * @param WP_Post $post Kudo-Karten-Post.
	 * @return array<string, mixed>
	 */
	private function map_card( WP_Post $post ) {
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

		$accent       = get_post_meta( $post->ID, '_bskudo_accent_color', true );
		$icon_pos     = get_post_meta( $post->ID, '_bskudo_icon_position', true );
		$back_branding = get_post_meta( $post->ID, '_bskudo_back_branding', true );

		if ( ! is_string( $back_branding ) || '' === trim( $back_branding ) ) {
			$legacy = get_post_meta( $post->ID, '_bskudo_impulse_text', true );
			$back_branding = is_string( $legacy ) ? $legacy : '';
		}

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
			'back_branding' => trim( (string) $back_branding ),
			'accent_color'  => $accent,
			'icon_position' => $icon_pos,
			'set_ids'       => array_map( 'intval', $set_ids ),
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
				'id'           => (int) $card['id'],
				'title'        => (string) $card['title'],
				'imageUrl'     => (string) $card['image_url'],
				'imageWidth'   => (int) ( $card['image_width'] ?? 0 ),
				'imageHeight'  => (int) ( $card['image_height'] ?? 0 ),
				'imageAlt'     => (string) $card['image_alt'],
				'accentColor'  => (string) $card['accent_color'],
				'iconPosition' => (string) $card['icon_position'],
				'backBranding' => (string) ( $card['back_branding'] ?? '' ),
				'setIds'       => isset( $card['set_ids'] ) ? $card['set_ids'] : array(),
			);
		}

		return $payload;
	}

	/**
	 * Textbausteine für JavaScript laden (mit Instance-Cache).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_textbausteine_for_js() {
		if ( null !== $this->textbausteine_cache ) {
			return $this->textbausteine_cache;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'kudo_textbaustein',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$items      = array();
		$char_limit = BSKudo_Settings::get_char_limit();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$text = wp_strip_all_tags( $post->post_content );
				if ( function_exists( 'mb_substr' ) ) {
					$text = mb_substr( $text, 0, $char_limit );
				} else {
					$text = substr( $text, 0, $char_limit );
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
		}

		wp_reset_postdata();

		$this->textbausteine_cache = $items;

		return $this->textbausteine_cache;
	}

	/**
	 * Datenschutzhinweis (Standard bis Backend-Einstellungen in Phase 6).
	 *
	 * @return string
	 */
	private function get_privacy_text() {
		$text = (string) BSKudo_Settings::get( 'security', 'privacy_text', '' );

		if ( '' === trim( $text ) ) {
			$text = __( 'Deine Angaben werden ausschließlich zum Versand dieser Kudo-Karte verwendet. Bei Sofortversand werden sie nicht dauerhaft gespeichert. Bei geplantem Versand werden sie bis zum Versandzeitpunkt temporär auf dem Server zwischengespeichert. Der Link zur Webansicht ist für eine begrenzte Zeit gültig und enthält deinen Karten-Text sowie deinen Namen als Absender.', 'bs-kudo-karten' );
		}

		return $text;
	}

	/**
	 * Standard-Text für die Karten-Rückseite.
	 *
	 * @return string
	 */
	private function get_default_branding_text() {
		$default = (string) BSKudo_Settings::get( 'branding', 'branding_text', '' );

		if ( '' === trim( $default ) ) {
			$default = (string) get_bloginfo( 'name' );
		}

		/**
		 * Standard-Branding auf der Karten-Rückseite anpassen.
		 *
		 * @param string $text Branding-Text.
		 */
		return (string) apply_filters( 'bskudo_default_branding_text', $default );
	}
}
