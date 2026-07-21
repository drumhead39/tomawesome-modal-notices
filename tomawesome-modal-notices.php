<?php
/**
 * Plugin Name:       TomAwesome Modal Notices
 * Plugin URI:        https://wordpress.org/plugins/tomawesome-modal-notices/
 * Description:       Create responsive, accessible information modals with flexible targeting, triggers, scheduling, and frequency controls.
 * Version:           1.1.2
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Thomas Clark
 * Text Domain:       tomawesome-modal-notices
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'IMFW_VERSION', '1.1.2' );
define( 'IMFW_FILE', __FILE__ );
define( 'IMFW_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMFW_URL', plugin_dir_url( __FILE__ ) );

require_once IMFW_DIR . 'includes/class-imfw-plugin.php';
require_once IMFW_DIR . 'includes/class-imfw-post-type.php';
require_once IMFW_DIR . 'includes/class-imfw-settings.php';
require_once IMFW_DIR . 'includes/class-imfw-admin.php';
require_once IMFW_DIR . 'includes/class-imfw-frontend.php';

function imfw() {
	return IMFW_Plugin::instance();
}

add_action( 'plugins_loaded', 'imfw' );
