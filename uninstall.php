<?php
/**
 * Uninstall handler for WP AI Info plugin.
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options.
delete_option( 'wp_ai_info_option_api_key' );
delete_option( 'wp_ai_info_option_prompt' );
