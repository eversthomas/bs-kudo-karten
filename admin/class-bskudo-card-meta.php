<?php
/**
 * Meta-Felder für den Post Type kudo_card (Backend).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rückseiten-Branding, Akzentfarbe und Icon-Position im Karten-Editor.
 */
class BSKudo_Card_Meta {

	/**
	 * Meta-Keys mit Prefix.
	 */
	const META_BACK_BRANDING = '_bskudo_back_branding';
	const META_ACCENT        = '_bskudo_accent_color';
	const META_ICON            = '_bskudo_icon_position';

	/**
	 * Hooks registrieren.
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_kudo_card', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Meta-Box für Kudo-Karten anzeigen.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'bskudo-card-details',
			__( 'Karten-Details', 'bs-kudo-karten' ),
			array( $this, 'render_meta_box' ),
			'kudo_card',
			'normal',
			'high'
		);
	}

	/**
	 * Meta-Box-Inhalt ausgeben.
	 *
	 * @param WP_Post $post Aktueller Post.
	 */
	public function render_meta_box( WP_Post $post ) {
		wp_nonce_field( 'bskudo_save_card_meta', 'bskudo_card_meta_nonce' );

		$back_branding = get_post_meta( $post->ID, self::META_BACK_BRANDING, true );
		$accent        = get_post_meta( $post->ID, self::META_ACCENT, true );
		$icon_pos      = get_post_meta( $post->ID, self::META_ICON, true );

		if ( ! is_string( $back_branding ) || '' === trim( $back_branding ) ) {
			$legacy = get_post_meta( $post->ID, '_bskudo_impulse_text', true );
			$back_branding = is_string( $legacy ) ? $legacy : '';
		}
		if ( ! is_string( $accent ) || '' === $accent ) {
			$accent = '#c45c3e';
		}
		if ( ! in_array( $icon_pos, array( 'left', 'center', 'right' ), true ) ) {
			$icon_pos = 'center';
		}
		?>
		<p>
			<label for="bskudo_back_branding"><strong><?php esc_html_e( 'Rückseiten-Branding (optional)', 'bs-kudo-karten' ); ?></strong></label><br>
			<textarea
				id="bskudo_back_branding"
				name="bskudo_back_branding"
				rows="3"
				class="large-text"
			><?php echo esc_textarea( $back_branding ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Überschreibt das globale Branding aus den Plugin-Einstellungen. Leer = Standard-Branding.', 'bs-kudo-karten' ); ?></span>
		</p>
		<p>
			<label for="bskudo_accent_color"><strong><?php esc_html_e( 'Akzentfarbe', 'bs-kudo-karten' ); ?></strong></label><br>
			<input
				type="color"
				id="bskudo_accent_color"
				name="bskudo_accent_color"
				value="<?php echo esc_attr( $accent ); ?>"
			>
		</p>
		<p>
			<label for="bskudo_icon_position"><strong><?php esc_html_e( 'Textausrichtung (Vorderseite)', 'bs-kudo-karten' ); ?></strong></label><br>
			<select id="bskudo_icon_position" name="bskudo_icon_position">
				<option value="left" <?php selected( $icon_pos, 'left' ); ?>><?php esc_html_e( 'Links', 'bs-kudo-karten' ); ?></option>
				<option value="center" <?php selected( $icon_pos, 'center' ); ?>><?php esc_html_e( 'Mitte', 'bs-kudo-karten' ); ?></option>
				<option value="right" <?php selected( $icon_pos, 'right' ); ?>><?php esc_html_e( 'Rechts', 'bs-kudo-karten' ); ?></option>
			</select>
		</p>
		<?php
	}

	/**
	 * Meta-Felder speichern.
	 *
	 * @param int     $post_id Post-ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['bskudo_card_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bskudo_card_meta_nonce'] ) ), 'bskudo_save_card_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$back_branding = isset( $_POST['bskudo_back_branding'] )
			? sanitize_textarea_field( wp_unslash( $_POST['bskudo_back_branding'] ) )
			: '';

		$accent = isset( $_POST['bskudo_accent_color'] )
			? sanitize_hex_color( wp_unslash( $_POST['bskudo_accent_color'] ) )
			: '';

		$icon_pos = isset( $_POST['bskudo_icon_position'] )
			? sanitize_key( wp_unslash( $_POST['bskudo_icon_position'] ) )
			: 'center';

		if ( ! in_array( $icon_pos, array( 'left', 'center', 'right' ), true ) ) {
			$icon_pos = 'center';
		}

		if ( ! $accent ) {
			$accent = '#c45c3e';
		}

		update_post_meta( $post_id, self::META_BACK_BRANDING, $back_branding );
		update_post_meta( $post_id, self::META_ACCENT, $accent );
		update_post_meta( $post_id, self::META_ICON, $icon_pos );
	}
}
