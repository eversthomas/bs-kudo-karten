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
	const META_QR_TARGET_URL   = '_bskudo_qr_target_url';

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
		$qr_target_url = get_post_meta( $post->ID, self::META_QR_TARGET_URL, true );
		$qr_target_url = is_string( $qr_target_url ) ? $qr_target_url : '';

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

		$back_layout = BSKudo_Card_Back_Layout::get_layout( $post->ID );
		$block_labels = BSKudo_Card_Back_Layout::get_block_labels();
		?>
		<div class="bskudo-meta">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_back_branding_col1"><?php esc_html_e( 'Rückseite: Spalte 1 (links – neben QR-Code)', 'bs-kudo-karten' ); ?></label>
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
					<p class="fhint"><?php esc_html_e( 'Optionaler Begleittext für die linke Spalte. Leer = Standard-Text / nur QR-Code.', 'bs-kudo-karten' ); ?></p>
				</div>
				<div class="field">
					<label class="flabel" for="bskudo_back_branding_col2"><?php esc_html_e( 'Rückseite: Spalte 2 (rechts – Branding)', 'bs-kudo-karten' ); ?></label>
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
					<p class="fhint"><?php esc_html_e( 'Das Haupt-Branding auf der Rückseite dieser Karte. Leer = Globaler Standard.', 'bs-kudo-karten' ); ?></p>
				</div>
				<div class="field">
					<label class="flabel" for="bskudo_qr_target_url"><?php esc_html_e( 'QR-Code auf der Kartenrückseite (Webansicht)', 'bs-kudo-karten' ); ?></label>
					<input
						type="url"
						class="input"
						id="bskudo_qr_target_url"
						name="bskudo_qr_target_url"
						value="<?php echo esc_attr( $qr_target_url ); ?>"
						placeholder="https://"
					>
					<p class="fhint"><?php esc_html_e( 'Gilt nur für den QR-Code auf der Rückseite der Online-Kudo-Karte (nach dem Öffnen des E-Mail-Links). Standard: erneut diese Webansicht. Trage eine eigene URL ein, wenn der Rückseiten-QR z. B. zu einer Spendenseite oder Aktion führen soll. Der QR-Code in der Benachrichtigungs-E-Mail führt immer zur Online-Karte und wird hier nicht beeinflusst.', 'bs-kudo-karten' ); ?></p>
				</div>
				<div class="field">
					<label class="flabel" for="bskudo_accent_color"><?php esc_html_e( 'Akzentfarbe', 'bs-kudo-karten' ); ?></label>
					<input
						type="color"
						class="input color"
						id="bskudo_accent_color"
						name="bskudo_accent_color"
						value="<?php echo esc_attr( $accent ); ?>"
					>
				</div>
				<div class="field">
					<label class="flabel" for="bskudo_icon_position"><?php esc_html_e( 'Textausrichtung (Vorderseite)', 'bs-kudo-karten' ); ?></label>
					<select class="select" id="bskudo_icon_position" name="bskudo_icon_position">
						<option value="left" <?php selected( $icon_pos, 'left' ); ?>><?php esc_html_e( 'Links', 'bs-kudo-karten' ); ?></option>
						<option value="center" <?php selected( $icon_pos, 'center' ); ?>><?php esc_html_e( 'Mitte', 'bs-kudo-karten' ); ?></option>
						<option value="right" <?php selected( $icon_pos, 'right' ); ?>><?php esc_html_e( 'Rechts', 'bs-kudo-karten' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label class="flabel"><?php esc_html_e( 'Rückseiten-Layout', 'bs-kudo-karten' ); ?></label>
					<p class="fhint"><?php esc_html_e( 'Lege fest, welche Bausteine in welcher Spalte und Reihenfolge auf der Kartenrückseite erscheinen. Ohne Anpassung gilt das bisherige Standard-Layout.', 'bs-kudo-karten' ); ?></p>
					<div class="bskudo-table-scroll">
						<table class="widefat striped">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Baustein', 'bs-kudo-karten' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Spalte', 'bs-kudo-karten' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Reihenfolge', 'bs-kudo-karten' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Sichtbar', 'bs-kudo-karten' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $back_layout as $block_id => $block_config ) : ?>
									<tr>
										<td><?php echo esc_html( $block_labels[ $block_id ] ?? $block_id ); ?></td>
										<td>
											<select class="select" name="bskudo_back_layout[<?php echo esc_attr( $block_id ); ?>][col]">
												<option value="1" <?php selected( (int) $block_config['col'], 1 ); ?>><?php esc_html_e( 'Spalte 1 (links)', 'bs-kudo-karten' ); ?></option>
												<option value="2" <?php selected( (int) $block_config['col'], 2 ); ?>><?php esc_html_e( 'Spalte 2 (rechts)', 'bs-kudo-karten' ); ?></option>
											</select>
										</td>
										<td>
											<input
												type="number"
												class="input"
												name="bskudo_back_layout[<?php echo esc_attr( $block_id ); ?>][order]"
												value="<?php echo esc_attr( (string) (int) $block_config['order'] ); ?>"
												min="1"
												max="99"
												step="1"
												style="max-width: 80px;"
											>
										</td>
										<td>
											<label>
												<input
													type="checkbox"
													name="bskudo_back_layout[<?php echo esc_attr( $block_id ); ?>][visible]"
													value="1"
													<?php checked( ! empty( $block_config['visible'] ) ); ?>
												>
												<?php esc_html_e( 'Anzeigen', 'bs-kudo-karten' ); ?>
											</label>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
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

		$qr_target_url = isset( $_POST['bskudo_qr_target_url'] )
			? trim( (string) wp_unslash( $_POST['bskudo_qr_target_url'] ) )
			: '';

		if ( '' !== $qr_target_url ) {
			$qr_target_url = esc_url_raw( $qr_target_url );
			if ( '' === $qr_target_url || ! wp_http_validate_url( $qr_target_url ) ) {
				$qr_target_url = '';
			}
		}

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
		update_post_meta( $post_id, self::META_QR_TARGET_URL, $qr_target_url );

		$layout_input = isset( $_POST['bskudo_back_layout'] ) ? wp_unslash( $_POST['bskudo_back_layout'] ) : array();
		$back_layout  = BSKudo_Card_Back_Layout::sanitize_for_save( $layout_input );
		update_post_meta( $post_id, BSKudo_Card_Back_Layout::META_KEY, $back_layout );
	}
}
