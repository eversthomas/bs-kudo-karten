<?php
/**
 * Karten-Liste: Übersicht (Phase 1) und Custom Columns (Phase 2).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard-light und List-Table-Erweiterungen für kudo_card.
 */
class BSKudo_Card_List {

	/**
	 * Hooks registrieren.
	 */
	public function register() {
		add_action( 'in_admin_header', array( $this, 'render_list_overview' ), 2 );
		add_filter( 'manage_kudo_card_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_kudo_card_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-kudo_card_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_accent' ) );
	}

	/**
	 * Übersicht oberhalb der WP-List-Table (innerhalb bskudo-wp-host).
	 */
	public function render_list_overview() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'edit-kudo_card' !== $screen->id ) {
			return;
		}

		$stats   = self::get_stats();
		$recent  = self::get_recent_cards();
		$new_url = admin_url( 'post-new.php?post_type=kudo_card' );
		?>
		<div class="bskudo-list-overview">
			<div class="page-head bskudo-list-head">
				<div>
					<h1><?php esc_html_e( 'Kudo-Karten', 'bs-kudo-karten' ); ?></h1>
					<p class="lede"><?php esc_html_e( 'Übersicht aller Karten, Sets und Textbausteine.', 'bs-kudo-karten' ); ?></p>
				</div>
				<div class="btn-row">
					<a href="<?php echo esc_url( $new_url ); ?>" class="btn primary">
						<?php esc_html_e( 'Neue Karte', 'bs-kudo-karten' ); ?>
					</a>
				</div>
			</div>

			<div class="stat-grid">
				<a class="card stat-card" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudo_card' ) ); ?>">
					<div class="card-body">
						<div class="stat-label"><?php esc_html_e( 'Kudo-Karten', 'bs-kudo-karten' ); ?></div>
						<div class="stat-value"><?php echo esc_html( (string) $stats['cards_publish'] ); ?></div>
						<div class="stat-sub">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: number of draft cards */
									__( '%d Entwürfe', 'bs-kudo-karten' ),
									$stats['cards_draft']
								)
							);
							?>
						</div>
					</div>
				</a>
				<a class="card stat-card" href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudo_textbaustein' ) ); ?>">
					<div class="card-body">
						<div class="stat-label"><?php esc_html_e( 'Textbausteine', 'bs-kudo-karten' ); ?></div>
						<div class="stat-value"><?php echo esc_html( (string) $stats['textbausteine'] ); ?></div>
						<div class="stat-sub"><?php esc_html_e( 'Vorlagen für Nachrichten', 'bs-kudo-karten' ); ?></div>
					</div>
				</a>
				<a class="card stat-card" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=kudo_set&post_type=kudo_card' ) ); ?>">
					<div class="card-body">
						<div class="stat-label"><?php esc_html_e( 'Kudo-Sets', 'bs-kudo-karten' ); ?></div>
						<div class="stat-value"><?php echo esc_html( (string) $stats['sets'] ); ?></div>
						<div class="stat-sub"><?php esc_html_e( 'Gruppierungen im Wizard', 'bs-kudo-karten' ); ?></div>
					</div>
				</a>
				<div class="card stat-card stat-card-static">
					<div class="card-body">
						<div class="stat-label"><?php esc_html_e( 'Shortcode', 'bs-kudo-karten' ); ?></div>
						<div class="stat-value stat-value-sm">
							<code class="key">[<?php echo esc_html( BSKudo_Shortcode::TAG ); ?>]</code>
						</div>
						<div class="stat-sub"><?php esc_html_e( 'Frontend-Einbindung', 'bs-kudo-karten' ); ?></div>
					</div>
				</div>
			</div>

			<div class="dash-grid">
				<div class="card">
					<div class="card-head">
						<h3><?php esc_html_e( 'Letzte Aktivität', 'bs-kudo-karten' ); ?></h3>
					</div>
					<?php if ( empty( $recent ) ) : ?>
						<div class="card-body">
							<p class="fhint"><?php esc_html_e( 'Noch keine Karten angelegt.', 'bs-kudo-karten' ); ?></p>
						</div>
					<?php else : ?>
						<table class="tbl tbl-compact">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Karte', 'bs-kudo-karten' ); ?></th>
									<th><?php esc_html_e( 'Set', 'bs-kudo-karten' ); ?></th>
									<th class="num"><?php esc_html_e( 'Geändert', 'bs-kudo-karten' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent as $card ) : ?>
									<tr class="row-click">
										<td>
											<a class="cell-strong" href="<?php echo esc_url( get_edit_post_link( $card->ID ) ); ?>">
												<?php echo esc_html( get_the_title( $card ) ?: __( '(Ohne Titel)', 'bs-kudo-karten' ) ); ?>
											</a>
										</td>
										<td class="cell-mute"><?php echo esc_html( self::get_card_set_label( $card->ID ) ); ?></td>
										<td class="num cell-mute"><?php echo esc_html( get_the_modified_date( '', $card ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<div class="dash-stack">
					<?php BSKudo_Admin::render_shortcode_panel(); ?>
					<div class="card">
						<div class="card-head"><h3><?php esc_html_e( 'Schnellzugriff', 'bs-kudo-karten' ); ?></h3></div>
						<div class="card-body quick-links">
							<a href="<?php echo esc_url( $new_url ); ?>" class="btn ghost">
								<?php esc_html_e( 'Neue Karte erstellen', 'bs-kudo-karten' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=kudo_textbaustein' ) ); ?>" class="btn subtle">
								<?php esc_html_e( 'Textbausteine verwalten', 'bs-kudo-karten' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . BSKudo_Admin::PAGE_SLUG ) ); ?>" class="btn subtle">
								<?php esc_html_e( 'Einstellungen', 'bs-kudo-karten' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Spalten definieren.
	 *
	 * @param array<string, string> $columns Bestehende Spalten.
	 * @return array<string, string>
	 */
	public function columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'cb' === $key ) {
				$new['cb']            = $label;
				$new['bskudo_thumb']  = __( 'Vorschau', 'bs-kudo-karten' );
				continue;
			}

			if ( 'date' === $key ) {
				$new['bskudo_accent'] = __( 'Akzent', 'bs-kudo-karten' );
			}

			$new[ $key ] = $label;
		}

		return $new;
	}

	/**
	 * Spalten-Inhalt rendern.
	 *
	 * @param string $column  Spalten-Slug.
	 * @param int    $post_id Post-ID.
	 */
	public function column_content( $column, $post_id ) {
		if ( 'bskudo_thumb' === $column ) {
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail(
					$post_id,
					array( 52, 52 ),
					array(
						'class' => 'bskudo-list-thumb',
						'alt'   => '',
					)
				);
				return;
			}

			echo '<span class="bskudo-list-thumb-empty" aria-hidden="true">—</span>';
			return;
		}

		if ( 'bskudo_accent' !== $column ) {
			return;
		}

		$accent = get_post_meta( $post_id, BSKudo_Card_Meta::META_ACCENT, true );
		if ( ! is_string( $accent ) || '' === $accent ) {
			$accent = '#c45c3e';
		}

		$safe_accent = sanitize_hex_color( $accent );
		if ( ! $safe_accent ) {
			$safe_accent = '#c45c3e';
		}

		printf(
			'<span class="bskudo-accent-swatch" style="background-color:%1$s" title="%2$s" aria-label="%2$s"></span>',
			esc_attr( $safe_accent ),
			esc_attr( $safe_accent )
		);
	}

	/**
	 * Sortierbare Spalten.
	 *
	 * @param array<string, string> $columns Spalten.
	 * @return array<string, string>
	 */
	public function sortable_columns( $columns ) {
		$columns['bskudo_accent'] = 'bskudo_accent';

		return $columns;
	}

	/**
	 * Sortierung nach Akzentfarbe (Meta).
	 *
	 * @param WP_Query $query Query.
	 */
	public function sort_by_accent( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'bskudo_accent' !== $query->get( 'orderby' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'edit-kudo_card' !== $screen->id ) {
			return;
		}

		$query->set( 'meta_key', BSKudo_Card_Meta::META_ACCENT );
		$query->set( 'orderby', 'meta_value' );
	}

	/**
	 * Zählwerte für Stat-Kacheln.
	 *
	 * @return array<string, int>
	 */
	private static function get_stats() {
		$cards = wp_count_posts( 'kudo_card' );
		$text  = wp_count_posts( 'kudo_textbaustein' );
		$sets  = wp_count_terms(
			array(
				'taxonomy'   => 'kudo_set',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $sets ) ) {
			$sets = 0;
		}

		return array(
			'cards_publish' => isset( $cards->publish ) ? (int) $cards->publish : 0,
			'cards_draft'   => isset( $cards->draft ) ? (int) $cards->draft : 0,
			'textbausteine' => isset( $text->publish ) ? (int) $text->publish : 0,
			'sets'          => (int) $sets,
		);
	}

	/**
	 * Zuletzt bearbeitete Karten.
	 *
	 * @return array<int, WP_Post>
	 */
	private static function get_recent_cards() {
		$posts = get_posts(
			array(
				'post_type'      => 'kudo_card',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 5,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Set-Label für eine Karte.
	 *
	 * @param int $post_id Post-ID.
	 * @return string
	 */
	private static function get_card_set_label( $post_id ) {
		$terms = get_the_terms( $post_id, 'kudo_set' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '—';
		}

		return implode( ', ', wp_list_pluck( $terms, 'name' ) );
	}
}
