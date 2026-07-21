<?php
defined( 'ABSPATH' ) || exit;

final class IMFW_Plugin {
	/** @var IMFW_Plugin|null */
	private static $instance;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		new IMFW_Post_Type();
		if ( is_admin() ) {
			new IMFW_Admin();
			add_action( 'admin_init', array( $this, 'privacy_policy_content' ) );
		}
		new IMFW_Frontend();
	}

	/**
	 * Suggest disclosure text in WordPress's Privacy Policy Guide.
	 */
	public function privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p class="privacy-policy-tutorial">' . esc_html__( 'TomAwesome Modal Notices does not send data to the plugin author or any external service.', 'tomawesome-modal-notices' ) . '</p>';
		$content .= '<p>' . esc_html__( 'When a modal uses a frequency setting other than “Every page load,” the plugin stores the modal ID and display time in the visitor’s browser using sessionStorage or localStorage. This information remains in the visitor’s browser and is used only to decide when that modal may appear again.', 'tomawesome-modal-notices' ) . '</p>';

		wp_add_privacy_policy_content(
			__( 'TomAwesome Modal Notices', 'tomawesome-modal-notices' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}
}
