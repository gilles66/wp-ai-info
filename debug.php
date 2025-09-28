<?php
/**
 * Les éléments de débogage.
 * Dernière modification de ce fichier : 20240905.
 */

$GLOBALS['ip_autorisees'] = [
	'83.156.180.79',
	// Viry Châtillon
	'78.216.63.36',
	// 66300
];

/**
 * Les actions filtrées sur IP.
 */
if ( in_array( $_SERVER['REMOTE_ADDR'], $GLOBALS['ip_autorisees'] ) ) {
	// Accepter la connexion quel que soit le mot de passe.
	// add_filter( 'check_password', '__return_true' );
}

if ( ! function_exists( 'is_json' ) ) {
	/**
	 * Indique si le paramètre passé est au format json.
	 *
	 * @param mixed $string Le paramètres à tester.
	 * @return bool
	 */
	function is_json( $string ) {
		// Vérifier que $data est bien une chaîne
		if ( ! is_string( $string ) ) {
			return false;
		}
		// Tente de décoder la chaîne JSON
		json_decode( $string );

		// Vérifie s'il y a eu une erreur lors du décodage
		return ( json_last_error() === JSON_ERROR_NONE );
	}
}

/**
 * Ne pas loguer les notices dans le wp-content/debug.log !
 */
if ( ! function_exists( 'gwp_dont_log_notices_callback' ) ) {
	function gwp_dont_log_notices_callback() {
		error_reporting( E_ALL & ~E_NOTICE );
	}
}
// add_action('init', 'gwp_dont_log_notices_callback');

if ( ! function_exists( 'pre' ) ) {
	function pre( $p, $color = null, $htmlentities = false ) {
		tag_pre_open( $color );
		if ( $htmlentities ) {
			$p = htmlentities( $p );
		}
		print_r( $p );
		tag_pre_close();
	}
}

if ( ! function_exists( 'prexit' ) ) {
	function prexit( $p, $color = null, $htmlentities = false ) {
		pre( $p, $color, $htmlentities );
		exit;
	}
}

if ( ! function_exists( 'pre2' ) ) {
	function pre2( $p, $color = null, $htmlentities = false ) {
		tag_pre_open( $color );
		if ( is_json( $p ) ) {
			$p = json_decode( $p );
		}
		if ( $htmlentities ) {
			$p = htmlentities( $p );
		}
		var_dump_indent( $p );
		tag_pre_close();
	}
}

if ( ! function_exists( 'prexit2' ) ) {
	function prexit2( $p, $color = null ) {
		pre2( $p, $color );
		exit;
	}
}

if ( ! function_exists( 'var_dump_indent' ) ) {
	function var_dump_indent( $variable ) {
		ob_start();
		var_dump( $variable );
		$output = ob_get_clean();
		$output = str_replace( '  [', '    [', $output );
		$output = str_replace( "=>\n", ' =>', $output );
		$output = str_replace( "=>  ", '=> ', $output );
		echo $output;
	}
}

if ( ! function_exists( 'pre3' ) ) {
	function pre3( $p, $color = null, $htmlentities = false ) {
		tag_pre_open( $color );
		if ( $htmlentities ) {
			$p = htmlentities( $p );
		}
		var_export( $p );
		tag_pre_close();
	}
}

if ( ! function_exists( 'prexit3' ) ) {
	function prexit3( $p, $color = null ) {
		pre3( $p, $color );
		exit;
	}
}

if ( ! function_exists( 'tag_pre_open' ) ) {
	function tag_pre_open( $color ) {
		$color = ( $color == null ) ? 'aquamarine' : $color;
		$margin = 'margin:10px;';
		$padding = 'padding:12px;';
		if ( function_exists( 'is_admin' ) ) {
			if ( is_admin() ) {
				$padding .= 'padding-left:235px;';
			}
		}
		echo '<pre style="box-sizing:border-box;' . $margin . $padding . 'text-align:left;font-size:0.9em;color:black;text-shadow:none !important;background:' . $color . ';border:none;border-radius:4px;-webkit-border-radius:4px;-moz-border-radius:4px;width:99%;overflow:inherit;font-family:monospace !important;">';
	}
}

if ( ! function_exists( 'tag_pre_close' ) ) {
	function tag_pre_close() {
		echo '</pre>';
	}
}

if ( ! function_exists( 'gwp_wp_footer_debug' ) ) {
	function gwp_wp_footer_debug() {
		if ( ! is_gilles_connecte() ) {
			return;
		}
		echo get_num_queries() . ' requêtes sql <br />';
		echo timer_stop( 0 ) . ' secondes<br />';
		if ( current_user_can( 'administrator' ) ) {
			global $wpdb;
			pre( $wpdb->queries );
		}
	}
}
// add_filter( 'wp_footer', 'gwp_wp_footer_debug', 999 );
// add_filter( 'admin_footer', 'gwp_wp_footer_debug', 999 );

if ( ! function_exists( 'gwplog' ) ) {
	function gwplog( $msg, $type_de_debug = 1 ) {
		$is_object = $is_array = false;
		$type_de_la_variable = gettype( $msg );

		if ( is_object( $msg ) ) {
			$is_object = true;
			$msg = serialize( $msg );
		}
		if ( is_array( $msg ) ) {
			$is_array = true;
			$msg = serialize( $msg );
		}
		if ( true === $msg ) {
			$msg = 'TRUE';
		}
		if ( false === $msg ) {
			$msg = 'FALSE';
		}

		$msg = (string) $msg;

		$new_str = '';
		for ( $i = 0; $i < strlen( $msg ); $i++ ) {
			$new_str .= ( ord( $msg[$i] ) != 0 ) ? $msg[$i] : ' ';
		}
		$msg = $new_str;

		if ( $is_object || $is_array ) {
			$href = add_query_arg( [
				'str'    => base64_encode( $msg ),
				'encode' => 'base64'
			], 'https://outils.perpi.bz/unserialize' );
			$msg = "<a target='_blank' href='$href' style='color:hotpink;'>$msg</a>";
		}

		if ( 2 === $type_de_debug ) {
			$msg = "La variable <strong style='color:blue;'>\$msg</strong> est de type <strong style='color:blue;'>$type_de_la_variable</strong> et vaut : $msg";
		}

		error_log( 'GWP LOG - ' . $msg );
	}
}
