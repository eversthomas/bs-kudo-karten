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
		$back_branding_col1 = get_post_meta( $post->ID, '_bskudo_back_branding_col1', true );
		$back_branding_col2 = get_post_meta( $post->ID, '_bskudo_back_branding_col2', true );
		$accent        = get_post_meta( $post->ID, self::META_ACCENT, true );
		$icon_pos      = get_post_meta( $post->ID, self::META_ICON, true );

		if ( ! is_string( $back_branding ) || '' === trim( $back_branding ) ) {
			$legacy = get_post_meta( $post->ID, '_bskudo_impulse_text', true );
			$back_branding = is_string( $legacy ) ? $legacy : '';
		}
		$col1_val = is_string( $back_branding_col1 ) ? $back_branding_col1 : '';
		$col2_val = is_string( $back_branding_col2 ) && '' !== trim( $back_branding_col2 ) ? $back_branding_col2 : ( is_string( $back_branding ) ? $back_branding : '' );

		if ( ! is_string( $accent ) || '' === $accent ) {
			$accent = '#c45c3e';
		}
		if ( ! in_array( $icon_pos, array( 'left', 'center', 'right' ), true ) ) {
			$icon_pos = 'center';
		}
		?>
		<div style="margin-bottom: 15px;">
			<label for="bskudo_back_branding_col1"><strong><?php esc_html_e( 'Rückseite: Spalte 1 (links - neben QR-Code)', 'bs-kudo-karten' ); ?></strong></label><br>
			<?php
			$editor_settings = array(
				'textarea_name' => 'bskudo_back_branding_col1',
				'media_buttons' => false,
				'textarea_rows' => 3,
				'teeny'         => true,
				'quicktags'     => true,
			);
			wp_editor( $col1_val, 'bskudo_back_branding_col1', $editor_settings );
			?>
			<span class="description"><?php esc_html_e( 'Optionaler Begleittext für die linke Spalte. Leer = Standard-Text / nur QR-Code.', 'bs-kudo-karten' ); ?></span>
		</div>
		<div style="margin-bottom: 15px;">
			<label for="bskudo_back_branding_col2"><strong><?php esc_html_e( 'Rückseite: Spalte 2 (rechts - Branding)', 'bs-kudo-karten' ); ?></strong></label><br>
			<?php
			$editor_settings = array(
				'textarea_name' => 'bskudo_back_branding_col2',
				'media_buttons' => false,
				'textarea_rows' => 3,
				'teeny'         => true,
				'quicktags'     => true,
			);
			wp_editor( $col2_val, 'bskudo_back_branding_col2', $editor_settings );
			?>
			<span class="description"><?php esc_html_e( 'Das Haupt-Branding auf der Rückseite dieser Karte. Leer = Globaler Standard.', 'bs-kudo-karten' ); ?></span>
		</div>
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
			? wp_kses_post( wp_unslash( $_POST['bskudo_back_branding'] ) )
			: '';

		$back_branding_col1 = isset( $_POST['bskudo_back_branding_col1'] )
			? wp_kses_post( wp_unslash( $_POST['bskudo_back_branding_col1'] ) )
			: '';

		$back_branding_col2 = isset( $_POST['bskudo_back_branding_col2'] )
			? wp_kses_post( wp_unslash( $_POST['bskudo_back_branding_col2'] ) )
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
		update_post_meta( $post_id, '_bskudo_back_branding_col1', $back_branding_col1 );
		update_post_meta( $post_id, '_bskudo_back_branding_col2', $back_branding_col2 );
		update_post_meta( $post_id, self::META_ACCENT, $accent );
		update_post_meta( $post_id, self::META_ICON, $icon_pos );
	}
}
