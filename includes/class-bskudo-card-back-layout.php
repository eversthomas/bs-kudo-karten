<?php
/**
 * Konfigurierbares Layout der Kartenrückseite (Webansicht + Druck).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liest, validiert und rendert Bausteine der Kartenrückseite.
 */
class BSKudo_Card_Back_Layout {

	const META_KEY = '_bskudo_back_layout';

	const BLOCK_QR   = 'qr';
	const BLOCK_COL1 = 'col1';
	const BLOCK_LOGO = 'logo';
	const BLOCK_COL2 = 'col2';

	/**
	 * Standard-Layout (entspricht dem bisherigen festen Aufbau).
	 *
	 * @return array<string, array{col: int, order: int, visible: bool}>
	 */
	public static function get_default_layout() {
		return array(
			self::BLOCK_QR   => array(
				'col'     => 1,
				'order'   => 1,
				'visible' => true,
			),
			self::BLOCK_COL1 => array(
				'col'     => 1,
				'order'   => 2,
				'visible' => true,
			),
			self::BLOCK_LOGO => array(
				'col'     => 2,
				'order'   => 1,
				'visible' => true,
			),
			self::BLOCK_COL2 => array(
				'col'     => 2,
				'order'   => 2,
				'visible' => true,
			),
		);
	}

	/**
	 * Baustein-Labels für das Backend.
	 *
	 * @return array<string, string>
	 */
	public static function get_block_labels() {
		return array(
			self::BLOCK_QR   => __( 'QR-Code', 'bs-kudo-karten' ),
			self::BLOCK_COL1 => __( 'Text Spalte 1', 'bs-kudo-karten' ),
			self::BLOCK_LOGO => __( 'Logo', 'bs-kudo-karten' ),
			self::BLOCK_COL2 => __( 'Text Spalte 2', 'bs-kudo-karten' ),
		);
	}

	/**
	 * Layout für eine Karte laden (mit Default-Fallback).
	 *
	 * @param int $card_id Post-ID.
	 * @return array<string, array{col: int, order: int, visible: bool}>
	 */
	public static function get_layout( $card_id ) {
		$stored = get_post_meta( absint( $card_id ), self::META_KEY, true );

		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return self::get_default_layout();
		}

		return self::merge_with_defaults( $stored );
	}

	/**
	 * Gespeichertes Layout mit Defaults zusammenführen.
	 *
	 * @param array<string, mixed> $stored Rohe Meta-Daten.
	 * @return array<string, array{col: int, order: int, visible: bool}>
	 */
	public static function merge_with_defaults( $stored ) {
		$defaults = self::get_default_layout();
		$merged   = array();

		foreach ( $defaults as $block_id => $default ) {
			$item = isset( $stored[ $block_id ] ) && is_array( $stored[ $block_id ] ) ? $stored[ $block_id ] : array();

			$col = isset( $item['col'] ) ? absint( $item['col'] ) : $default['col'];
			if ( $col < 1 || $col > 2 ) {
				$col = $default['col'];
			}

			$order = isset( $item['order'] ) ? absint( $item['order'] ) : $default['order'];
			if ( $order < 1 ) {
				$order = $default['order'];
			}

			$visible = array_key_exists( 'visible', $item ) ? (bool) $item['visible'] : $default['visible'];

			$merged[ $block_id ] = array(
				'col'     => $col,
				'order'   => $order,
				'visible' => $visible,
			);
		}

		return $merged;
	}

	/**
	 * POST-Daten für das Layout bereinigen.
	 *
	 * @param array<string, mixed>|mixed $input Rohe POST-Daten.
	 * @return array<string, array{col: int, order: int, visible: bool}>
	 */
	public static function sanitize_for_save( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_default_layout();
		}

		$clean    = array();
		$defaults = self::get_default_layout();

		foreach ( $defaults as $block_id => $default ) {
			$item = isset( $input[ $block_id ] ) && is_array( $input[ $block_id ] ) ? $input[ $block_id ] : array();

			$col = isset( $item['col'] ) ? absint( $item['col'] ) : $default['col'];
			if ( $col < 1 || $col > 2 ) {
				$col = $default['col'];
			}

			$order = isset( $item['order'] ) ? absint( $item['order'] ) : $default['order'];
			if ( $order < 1 ) {
				$order = 1;
			}

			$clean[ $block_id ] = array(
				'col'     => $col,
				'order'   => $order,
				'visible' => ! empty( $item['visible'] ),
			);
		}

		return $clean;
	}

	/**
	 * Sichtbare Bausteine einer Spalte, sortiert nach Reihenfolge.
	 *
	 * @param array<string, array{col: int, order: int, visible: bool}> $layout  Layout-Konfiguration.
	 * @param int                                                       $column  Spalte (1 oder 2).
	 * @return array<int, string> Block-IDs.
	 */
	public static function get_blocks_for_column( $layout, $column ) {
		$column = absint( $column );
		$items  = array();

		foreach ( $layout as $block_id => $config ) {
			if ( empty( $config['visible'] ) || (int) $config['col'] !== $column ) {
				continue;
			}

			$items[] = array(
				'id'    => $block_id,
				'order' => isset( $config['order'] ) ? (int) $config['order'] : 99,
			);
		}

		usort(
			$items,
			static function ( $a, $b ) {
				if ( $a['order'] === $b['order'] ) {
					return 0;
				}

				return ( $a['order'] < $b['order'] ) ? -1 : 1;
			}
		);

		return array_map(
			static function ( $item ) {
				return $item['id'];
			},
			$items
		);
	}

	/**
	 * Einzelnen Baustein der Rückseite ausgeben.
	 *
	 * @param string               $block_id Baustein-Kennung.
	 * @param array<string, mixed> $context  Render-Kontext.
	 */
	public static function render_block( $block_id, $context ) {
		switch ( $block_id ) {
			case self::BLOCK_QR:
				self::render_qr_block( $context );
				break;
			case self::BLOCK_COL1:
				self::render_col1_block( $context );
				break;
			case self::BLOCK_LOGO:
				self::render_logo_block( $context );
				break;
			case self::BLOCK_COL2:
				self::render_col2_block( $context );
				break;
		}
	}

	/**
	 * QR-Code-Baustein.
	 *
	 * @param array<string, mixed> $context Render-Kontext.
	 */
	private static function render_qr_block( $context ) {
		$qr_code_data_uri = isset( $context['qr_code_data_uri'] ) ? (string) $context['qr_code_data_uri'] : '';

		if ( '' === $qr_code_data_uri ) {
			return;
		}
		?>
		<div class="bskudo-cardview__qr-wrap">
			<img class="bskudo-cardview__qr-image" src="<?php echo esc_attr( $qr_code_data_uri ); ?>" alt="<?php esc_attr_e( 'Kudo-Karte QR-Code', 'bs-kudo-karten' ); ?>">
		</div>
		<?php
	}

	/**
	 * Text-Spalte 1.
	 *
	 * @param array<string, mixed> $context Render-Kontext.
	 */
	private static function render_col1_block( $context ) {
		$branding_col1 = isset( $context['branding_col1'] ) ? (string) $context['branding_col1'] : '';
		?>
		<div class="bskudo-cardview__col-content">
			<?php if ( '' !== $branding_col1 ) : ?>
				<?php echo wp_kses_post( $branding_col1 ); ?>
			<?php else : ?>
				<p style="font-size: 11px; margin-top: 8px; opacity: 0.75;"><?php esc_html_e( 'Scanne diesen Code, um die digitale Kudo-Karte online aufzurufen.', 'bs-kudo-karten' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Logo-Baustein.
	 *
	 * @param array<string, mixed> $context Render-Kontext.
	 */
	private static function render_logo_block( $context ) {
		$logo_url = isset( $context['logo_url'] ) ? (string) $context['logo_url'] : '';

		if ( '' === $logo_url ) {
			return;
		}
		?>
		<div class="bskudo-cardview__logo-wrap" style="margin-bottom: 15px; text-align: center;">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width: 140px; height: auto; display: inline-block;">
		</div>
		<?php
	}

	/**
	 * Text-Spalte 2.
	 *
	 * @param array<string, mixed> $context Render-Kontext.
	 */
	private static function render_col2_block( $context ) {
		$branding_col2 = isset( $context['branding_col2'] ) ? (string) $context['branding_col2'] : '';
		?>
		<div class="bskudo-cardview__col-content">
			<?php echo wp_kses_post( $branding_col2 ); ?>
		</div>
		<?php
	}
}
