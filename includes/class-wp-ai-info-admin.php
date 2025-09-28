<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_AI_Info_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
    }

    public function register_menu() {
        add_options_page(
            __( 'WP AI Info Settings', 'wp-ai-info' ),
            __( 'WP AI Info', 'wp-ai-info' ),
            'manage_options',
            WP_AI_Info::SLUG_PAGE,
            [ $this, 'settings_page' ]
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'WP AI Info Settings', 'wp-ai-info' ) . '</h1></div>';
    }
}
