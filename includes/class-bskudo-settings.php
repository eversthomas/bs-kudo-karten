<?php
/**
 * Plugin-Einstellungen (Option bskudo_settings).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liest und speichert zentrale Plugin-Optionen.
 */
class BSKudo_Settings {

	const OPTION = 'bskudo_settings';

	/**
	 * Standardwerte aller Einstellungsbereiche.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_defaults() {
		return array(
			'general'  => array(
				'sender_name'         => get_bloginfo( 'name' ),
				'sender_email'        => get_option( 'admin_email' ),
				'subject_template'    => __( '{sender} sendet dir eine Kudo-Karte', 'bs-kudo-karten' ),
				'copy_to_sender'      => false,
				'enable_send_to_self' => true,
				'enable_delayed_send' => true,
				'schedule_max_days'   => 30,
				'show_qr_in_mail'     => true,
			),
			'branding' => array(
				'logo_id'            => 0,
				'primary_color'      => '#c45c3e',
				'branding_text'      => (string) get_bloginfo( 'name' ),
				'branding_text_col1' => '',
				'branding_text_col2' => '',
				'global_branding_text' => '',
				'mail_footer_text'   => '',
				'footer_powered'     => true,
			),
			'security' => array(
				'rate_limit'      => 5,
				'char_limit'      => BSKUDO_CHAR_LIMIT,
				'privacy_text'    => __( 'Deine Angaben werden ausschließlich zum Versand dieser Kudo-Karte verwendet. Bei Sofortversand werden sie nicht dauerhaft gespeichert. Bei geplantem Versand werden sie bis zum Versandzeitpunkt temporär auf dem Server zwischengespeichert. Der Link zur Webansicht ist für eine begrenzte Zeit gültig und enthält deinen Karten-Text sowie deinen Namen als Absender.', 'bs-kudo-karten' ),
				'token_ttl_days'  => 30,
			),
		);
	}

	/**
	 * Alle Einstellungen mit Defaults mergen.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_all() {
		$stored   = get_option( self::OPTION, array() );
		$defaults = self::get_defaults();

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array(
			'general'  => wp_parse_args(
				isset( $stored['general'] ) && is_array( $stored['general'] ) ? $stored['general'] : array(),
				$defaults['general']
			),
			'branding' => wp_parse_args(
				isset( $stored['branding'] ) && is_array( $stored['branding'] ) ? $stored['branding'] : array(),
				$defaults['branding']
			),
			'security' => wp_parse_args(
				isset( $stored['security'] ) && is_array( $stored['security'] ) ? $stored['security'] : array(),
				$defaults['security']
			),
		);
	}

	/**
	 * Einzelnen Wert lesen.
	 *
	 * @param string $section Bereich (general, branding, security).
	 * @param string $key     Schlüssel.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get( $section, $key, $default = '' ) {
		$all = self::get_all();

		if ( ! isset( $all[ $section ][ $key ] ) ) {
			return $default;
		}

		return $all[ $section ][ $key ];
	}

	/**
	 * Zeichenlimit für Karten-Text.
	 *
	 * @return int
	 */
	public static function get_char_limit() {
		$limit = (int) self::get( 'security', 'char_limit', BSKUDO_CHAR_LIMIT );

		if ( $limit < 1 ) {
			$limit = BSKUDO_CHAR_LIMIT;
		}

		return $limit;
	}

	/**
	 * Rate-Limit pro IP und Stunde.
	 *
	 * @return int
	 */
	public static function get_rate_limit() {
		$limit = (int) self::get( 'security', 'rate_limit', 5 );

		if ( $limit < 1 ) {
			$limit = 5;
		}

		return $limit;
	}

	/**
	 * Gültigkeit von Webansicht-Token in Tagen.
	 *
	 * @return int
	 */
	public static function is_feature_enabled( $key ) {
		return (bool) self::get( 'general', $key, false );
	}

	/**
	 * Maximaler Vorlauf für geplanten Versand (Tage).
	 *
	 * @return int
	 */
	public static function get_schedule_max_days() {
		$days = (int) self::get( 'general', 'schedule_max_days', 30 );

		if ( $days < 1 ) {
			$days = 30;
		}

		return $days;
	}

	/**
	 * Gültigkeit von Webansicht-Token in Tagen.
	 *
	 * @return int
	 */
	public static function get_token_ttl_days() {
		$days = (int) self::get( 'security', 'token_ttl_days', 30 );

		if ( $days < 1 ) {
			$days = 30;
		}

		if ( $days > 365 ) {
			$days = 365;
		}

		return $days;
	}

	/**
	 * Einstellungen beim Speichern bereinigen.
	 *
	 * @param array<string, mixed>|mixed $input Rohe POST-Daten.
	 * @return array<string, array<string, mixed>>
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_all();
		}

		$defaults = self::get_defaults();
		$clean    = self::get_all();

		if ( isset( $input['general'] ) && is_array( $input['general'] ) ) {
			$g = $input['general'];
			$clean['general']['sender_name']      = isset( $g['sender_name'] ) ? sanitize_text_field( wp_unslash( $g['sender_name'] ) ) : $defaults['general']['sender_name'];
			$clean['general']['sender_email']     = isset( $g['sender_email'] ) ? sanitize_email( wp_unslash( $g['sender_email'] ) ) : $defaults['general']['sender_email'];
			$clean['general']['subject_template'] = isset( $g['subject_template'] ) ? sanitize_text_field( wp_unslash( $g['subject_template'] ) ) : $defaults['general']['subject_template'];
			$clean['general']['copy_to_sender']       = ! empty( $g['copy_to_sender'] );
			$clean['general']['enable_send_to_self']  = ! empty( $g['enable_send_to_self'] );
			$clean['general']['enable_delayed_send']  = ! empty( $g['enable_delayed_send'] );
			$clean['general']['schedule_max_days']    = isset( $g['schedule_max_days'] ) ? max( 1, min( 90, absint( $g['schedule_max_days'] ) ) ) : $defaults['general']['schedule_max_days'];
			$clean['general']['show_qr_in_mail']       = ! empty( $g['show_qr_in_mail'] );
		}

		if ( isset( $input['branding'] ) && is_array( $input['branding'] ) ) {
			$b = $input['branding'];
			$clean['branding']['logo_id']            = isset( $b['logo_id'] ) ? absint( $b['logo_id'] ) : 0;
			$color                                   = isset( $b['primary_color'] ) ? sanitize_hex_color( wp_unslash( $b['primary_color'] ) ) : '';
			$clean['branding']['primary_color']      = $color ? $color : $defaults['branding']['primary_color'];
			$clean['branding']['branding_text']      = isset( $b['branding_text'] ) ? wp_kses_post( wp_unslash( $b['branding_text'] ) ) : $defaults['branding']['branding_text'];
			$clean['branding']['branding_text_col1'] = isset( $b['branding_text_col1'] ) ? wp_kses_post( wp_unslash( $b['branding_text_col1'] ) ) : '';
			$clean['branding']['branding_text_col2'] = isset( $b['branding_text_col2'] ) ? wp_kses_post( wp_unslash( $b['branding_text_col2'] ) ) : '';
			$clean['branding']['global_branding_text'] = isset( $b['global_branding_text'] ) ? sanitize_textarea_field( wp_unslash( $b['global_branding_text'] ) ) : '';
			$clean['branding']['mail_footer_text']   = isset( $b['mail_footer_text'] ) ? sanitize_textarea_field( wp_unslash( $b['mail_footer_text'] ) ) : '';
			$clean['branding']['footer_powered']     = ! empty( $b['footer_powered'] );
		}

		if ( isset( $input['security'] ) && is_array( $input['security'] ) ) {
			$s = $input['security'];
			$clean['security']['rate_limit']   = isset( $s['rate_limit'] ) ? max( 1, absint( $s['rate_limit'] ) ) : $defaults['security']['rate_limit'];
			$clean['security']['char_limit']   = isset( $s['char_limit'] ) ? max( 1, absint( $s['char_limit'] ) ) : $defaults['security']['char_limit'];
			$clean['security']['privacy_text']   = isset( $s['privacy_text'] ) ? sanitize_textarea_field( wp_unslash( $s['privacy_text'] ) ) : $defaults['security']['privacy_text'];
			$clean['security']['token_ttl_days'] = isset( $s['token_ttl_days'] ) ? max( 1, min( 365, absint( $s['token_ttl_days'] ) ) ) : $defaults['security']['token_ttl_days'];
		}

		return $clean;
	}
}
