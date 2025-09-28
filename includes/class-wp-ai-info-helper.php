<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_AI_Info_Helper {

    public static function encrypt( $data ) {
        $key = WP_AI_Info::ENCRYPTION_KEY;
        $iv = openssl_random_pseudo_bytes( 16 );
        $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
        return base64_encode( $iv . $encrypted );
    }

    public static function decrypt( $data ) {
        $key = WP_AI_Info::ENCRYPTION_KEY;
        $data = base64_decode( $data );
        $iv = substr( $data, 0, 16 );
        $encrypted = substr( $data, 16 );
        return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
    }
}
