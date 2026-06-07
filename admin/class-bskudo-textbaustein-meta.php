<?php
/**
 * Meta-Felder für den Post Type kudo_textbaustein (Backend).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zuordnung zu Kudo-Karte oder Kudo-Set(s).
 */
class BSKudo_Textbaustein_Meta {

	const META_CARD = '_bskudo_linked_card';
	const META_SETS = '_bskudo_linked_sets';

	/**
	 * Hooks registrieren.
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_kudo_textbaustein', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Meta-Box registrieren.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'bskudo-textbaustein-links',
			__( 'Zuordnung', 'bs-kudo-karten' ),
			array( $this, 'render_meta_box' ),
			'kudo_textbaustein',
			'side',
			'default'
		);
	}

	/**
	 * Meta-Box ausgeben.
	 *
	 * @param WP_Post $post Aktueller Post.
	 */
	public function render_meta_box( WP_Post $post ) {
		wp_nonce_field( 'bskudo_save_textbaustein_meta', 'bskudo_textbaustein_meta_nonce' );

		$linked_card = (int) get_post_meta( $post->ID, self::META_CARD, true );
		$linked_sets = get_post_meta( $post->ID, self::META_SETS, true );

		if ( ! is_array( $linked_sets ) ) {
			$linked_sets = array();
		}

		$linked_sets = array_map( 'intval', $linked_sets );

		$cards = get_posts(
			array(
				'post_type'      => 'kudo_card',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$sets = get_terms(
			array(
				'taxonomy'   => 'kudo_set',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $sets ) ) {
			$sets = array();
		}
		?>
		<div class="bskudo-meta">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_linked_card"><?php esc_html_e( 'Kudo-Karte', 'bs-kudo-karten' ); ?></label>
					<select id="bskudo_linked_card" name="bskudo_linked_card" class="select">
						<option value="0"><?php esc_html_e( '— Keine —', 'bs-kudo-karten' ); ?></option>
						<?php foreach ( $cards as $card ) : ?>
							<option value="<?php echo esc_attr( (string) $card->ID ); ?>" <?php selected( $linked_card, $card->ID ); ?>>
								<?php echo esc_html( get_the_title( $card ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="field">
					<span class="flabel"><?php esc_html_e( 'Kudo-Sets', 'bs-kudo-karten' ); ?></span>
					<p class="fhint"><?php esc_html_e( 'Leer = für alle Sets. Ohne Karte/Set = globaler Textbaustein.', 'bs-kudo-karten' ); ?></p>
					<?php if ( empty( $sets ) ) : ?>
						<p class="fhint"><?php esc_html_e( 'Noch keine Sets angelegt.', 'bs-kudo-karten' ); ?></p>
					<?php else : ?>
						<ul class="check-list">
							<?php foreach ( $sets as $set ) : ?>
								<li>
									<label class="check-row">
										<input
											type="checkbox"
											name="bskudo_linked_sets[]"
											value="<?php echo esc_attr( (string) $set->term_id ); ?>"
											<?php checked( in_array( (int) $set->term_id, $linked_sets, true ) ); ?>
										>
										<span><?php echo esc_html( $set->name ); ?></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Meta speichern und Textlänge begrenzen.
	 *
	 * @param int     $post_id Post-ID.
	 * @param WP_Post $post    Post-Objekt.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['bskudo_textbaustein_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bskudo_textbaustein_meta_nonce'] ) ), 'bskudo_save_textbaustein_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$linked_card = isset( $_POST['bskudo_linked_card'] )
			? absint( $_POST['bskudo_linked_card'] )
			: 0;

		$linked_sets = array();
		if ( isset( $_POST['bskudo_linked_sets'] ) && is_array( $_POST['bskudo_linked_sets'] ) ) {
			$linked_sets = array_map( 'absint', wp_unslash( $_POST['bskudo_linked_sets'] ) );
			$linked_sets = array_values( array_filter( $linked_sets ) );
		}

		update_post_meta( $post_id, self::META_CARD, $linked_card );
		update_post_meta( $post_id, self::META_SETS, $linked_sets );

		// Text im Editor auf Zeichenlimit kürzen.
		$content = wp_strip_all_tags( $post->post_content );
		if ( function_exists( 'mb_substr' ) ) {
			$content = mb_substr( $content, 0, BSKUDO_CHAR_LIMIT );
		} else {
			$content = substr( $content, 0, BSKUDO_CHAR_LIMIT );
		}

		if ( $content !== wp_strip_all_tags( $post->post_content ) ) {
			remove_action( 'save_post_kudo_textbaustein', array( $this, 'save_meta' ), 10 );
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
				)
			);
			add_action( 'save_post_kudo_textbaustein', array( $this, 'save_meta' ), 10, 2 );
		}
	}
}
