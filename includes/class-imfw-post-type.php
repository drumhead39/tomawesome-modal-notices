<?php
/**
 * Information modal post type.
 *
 * @package TomAwesomeModalNotices
 */

defined( 'ABSPATH' ) || exit;

class IMFW_Post_Type {
	const TYPE = 'imfw_modal';

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		$labels = array(
			'name'          => __( 'Modal Notices', 'tomawesome-modal-notices' ),
			'singular_name' => __( 'Modal Notice', 'tomawesome-modal-notices' ),
			'add_new'       => __( 'Add New', 'tomawesome-modal-notices' ),
			'add_new_item'  => __( 'Add New Modal Notice', 'tomawesome-modal-notices' ),
			'edit_item'     => __( 'Edit Modal Notice', 'tomawesome-modal-notices' ),
			'new_item'      => __( 'New Modal Notice', 'tomawesome-modal-notices' ),
			'view_item'     => __( 'Preview Modal Notice', 'tomawesome-modal-notices' ),
			'search_items'  => __( 'Search Modal Notices', 'tomawesome-modal-notices' ),
			'not_found'     => __( 'No modal notices found.', 'tomawesome-modal-notices' ),
			'menu_name'     => __( 'Modal Notices', 'tomawesome-modal-notices' ),
		);
		register_post_type(
			self::TYPE,
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => false,
				'menu_icon'       => 'dashicons-welcome-view-site',
				'supports'        => array( 'title', 'editor', 'revisions' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}
}
