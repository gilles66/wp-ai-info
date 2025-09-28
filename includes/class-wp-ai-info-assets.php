<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_AI_Info_Assets {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style( 'wp-ai-info-admin', WPAIINFO_URL . 'assets/admin.css', [], '1.0.0' );
        wp_enqueue_script( 'wp-ai-info-admin', WPAIINFO_URL . 'assets/admin.js', [ 'jquery' ], '1.0.0', true );
    }
}
