<?php
/*
Plugin Name: WP AI Info
Description: Insert blog posts automatically using AI.
Version: 1.0.0
Author: Gilles Dumas
Author URI: https://gillesdumas.com
Text Domain: wp-ai-info
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define constants
define( 'WPAIINFO_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPAIINFO_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once WPAIINFO_PATH . 'includes/class-wp-ai-info.php';

// Init plugin
function wpaiinfo_init() {
    $GLOBALS['wp_ai_info'] = new WP_AI_Info();
}
add_action( 'plugins_loaded', 'wpaiinfo_init' );

// Activation/Deactivation hooks
function wpaiinfo_activate() {
    // Code d'activation (si nécessaire)
}
register_activation_hook( __FILE__, 'wpaiinfo_activate' );

function wpaiinfo_deactivate() {
    // Code de désactivation (si nécessaire)
}
register_deactivation_hook( __FILE__, 'wpaiinfo_deactivate' );
