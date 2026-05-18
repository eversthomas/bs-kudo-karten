<?php
/**
 * Custom Post Types und Taxonomien für BS Kudo Karten.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert kudo_card, kudo_set (Taxonomie) und kudo_textbaustein.
 */
class BSKudo_CPT {

	/**
	 * Post Type: Kudo-Karte.
	 */
	public function register_post_types() {
		register_post_type(
			'kudo_card',
			array(
				'labels'              => array(
					'name'               => __( 'Kudo-Karten', 'bs-kudo-karten' ),
					'singular_name'      => __( 'Kudo-Karte', 'bs-kudo-karten' ),
					'add_new'            => __( 'Neue Karte', 'bs-kudo-karten' ),
					'add_new_item'       => __( 'Neue Kudo-Karte hinzufügen', 'bs-kudo-karten' ),
					'edit_item'          => __( 'Kudo-Karte bearbeiten', 'bs-kudo-karten' ),
					'new_item'           => __( 'Neue Kudo-Karte', 'bs-kudo-karten' ),
					'view_item'          => __( 'Kudo-Karte ansehen', 'bs-kudo-karten' ),
					'search_items'       => __( 'Kudo-Karten suchen', 'bs-kudo-karten' ),
					'not_found'          => __( 'Keine Kudo-Karten gefunden', 'bs-kudo-karten' ),
					'not_found_in_trash' => __( 'Keine Kudo-Karten im Papierkorb', 'bs-kudo-karten' ),
					'menu_name'          => __( 'Kudo Karten', 'bs-kudo-karten' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-heart',
				'menu_position'       => 25,
				'capability_type'     => 'post',
				'hierarchical'        => false,
				'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_rest'        => false,
			)
		);

		register_post_type(
			'kudo_textbaustein',
			array(
				'labels'              => array(
					'name'               => __( 'Textbausteine', 'bs-kudo-karten' ),
					'singular_name'      => __( 'Textbaustein', 'bs-kudo-karten' ),
					'add_new'            => __( 'Neuer Textbaustein', 'bs-kudo-karten' ),
					'add_new_item'       => __( 'Neuen Textbaustein hinzufügen', 'bs-kudo-karten' ),
					'edit_item'          => __( 'Textbaustein bearbeiten', 'bs-kudo-karten' ),
					'new_item'           => __( 'Neuer Textbaustein', 'bs-kudo-karten' ),
					'view_item'          => __( 'Textbaustein ansehen', 'bs-kudo-karten' ),
					'search_items'       => __( 'Textbausteine suchen', 'bs-kudo-karten' ),
					'not_found'          => __( 'Keine Textbausteine gefunden', 'bs-kudo-karten' ),
					'not_found_in_trash' => __( 'Keine Textbausteine im Papierkorb', 'bs-kudo-karten' ),
					'menu_name'          => __( 'Textbausteine', 'bs-kudo-karten' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=kudo_card',
				'capability_type'     => 'post',
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_rest'        => false,
			)
		);
	}

	/**
	 * Taxonomie: Kudo-Set (Gruppierung für Karten).
	 */
	public function register_taxonomies() {
		register_taxonomy(
			'kudo_set',
			array( 'kudo_card' ),
			array(
				'labels'            => array(
					'name'              => __( 'Kudo-Sets', 'bs-kudo-karten' ),
					'singular_name'     => __( 'Kudo-Set', 'bs-kudo-karten' ),
					'search_items'      => __( 'Sets suchen', 'bs-kudo-karten' ),
					'all_items'         => __( 'Alle Sets', 'bs-kudo-karten' ),
					'parent_item'       => __( 'Übergeordnetes Set', 'bs-kudo-karten' ),
					'parent_item_colon' => __( 'Übergeordnetes Set:', 'bs-kudo-karten' ),
					'edit_item'         => __( 'Set bearbeiten', 'bs-kudo-karten' ),
					'update_item'       => __( 'Set aktualisieren', 'bs-kudo-karten' ),
					'add_new_item'      => __( 'Neues Set hinzufügen', 'bs-kudo-karten' ),
					'new_item_name'     => __( 'Neuer Set-Name', 'bs-kudo-karten' ),
					'menu_name'         => __( 'Kudo-Sets', 'bs-kudo-karten' ),
				),
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_menu'      => 'edit.php?post_type=kudo_card',
				'query_var'         => false,
				'rewrite'           => false,
				'show_in_rest'      => false,
			)
		);
	}
}
