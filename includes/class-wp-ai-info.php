<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_AI_Info {

    const ENCRYPTION_KEY = 'Y0AT8xhF8xAm0ZDAxThkuFkrsrMrnxzs';
    const SLUG_PAGE = 'wp-ai-info-options';

    public function __construct() {
        require_once WPAIINFO_PATH . 'includes/third-party/Parsedown.php';
        require_once WPAIINFO_PATH . 'includes/third-party/debug.php';
        require_once WPAIINFO_PATH . 'includes/class-wp-ai-info-admin.php';
        require_once WPAIINFO_PATH . 'includes/class-wp-ai-info-assets.php';
        require_once WPAIINFO_PATH . 'includes/class-wp-ai-info-helper.php';

        // Init admin
        new WP_AI_Info_Admin();

        // Init assets
        new WP_AI_Info_Assets();
    }
}
