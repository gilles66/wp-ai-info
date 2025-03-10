<?php
/**
 * Les éléments de débogage.
 * Dernière modification de ce fichier : 20240905.
 */


/**
 * Ne pas loguer les notices dans le wp-content/debug.log !
 *
 * @author Gilles Dumas <circusmind@gmail.com>
 * @since  20180910
 * @link   http://www.php.net/manual/en/function.error-reporting.php
 */
function gwp_dont_log_notices_callback() {
	error_reporting( E_ALL & ~E_NOTICE );
}

// add_action('init', 'gwp_dont_log_notices_callback');

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
 * Affiche une variable de manière claire.
 *
 * @param $p            string La variable à afficher.
 * @param $color        string La couleur de fond.
 * @param $htmlentities bool   Convertit tous les caractères éligibles en entités HTML.
 * @return void
 */
function pre( $p, $color = null, $htmlentities = false ) {
	tag_pre_open( $color );

	// @todo remettre la fonction is_json()
	//	if ( is_json( $p ) ) {
	//		$p = json_decode( $p );
	//	}

	if ( $htmlentities ) {
		$p = htmlentities( $p );
	}

	print_r( $p );
	tag_pre_close();
}

/**
 * prexit()
 */
function prexit( $p, $color = null, $htmlentities = false ) {
	pre( $p, $color, $htmlentities );
	exit;
}

/**
 * pre2()
 */
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

/**
 * prexit2()
 */
function prexit2( $p, $color = null ) {
	pre2( $p, $color );
	exit;
}

/**
 * Formate la fonction var_dump() pour une meilleure lisibilité.
 *
 * @param $variable mixed La valeur à analyser.
 * @return void
 */
function var_dump_indent( $variable ) {
	ob_start();
	var_dump( $variable );
	$output = ob_get_clean();
	$output = str_replace( '  [', '    [', $output );
	$output = str_replace( "=>\n", ' =>', $output );
	$output = str_replace( "=>  ", '=> ', $output );
	echo $output;
}

/**
 * pre3()
 */
function pre3( $p, $color = null, $htmlentities = false ) {
	tag_pre_open( $color );

	if ( $htmlentities ) {
		$p = htmlentities( $p );
	}
	var_export( $p );
	tag_pre_close();
}

/**
 * prexit3()
 */
function prexit3( $p, $color = null ) {
	pre3( $p, $color );
	exit;
}

/**
 * tag_pre_open()
 */
function tag_pre_open( $color ) {
	$color = ( $color == null ) ? 'aquamarine' : $color; // powderblue
	$margin = 'margin:10px;';
	$padding = 'padding:12px;';

	if ( function_exists( 'is_admin' ) ) {
		if ( is_admin() ) {
			$padding .= 'padding-left:235px;';
		}
	}

	echo '<pre style="box-sizing:border-box;' . $margin . $padding . 'text-align:left;font-size:0.9em;color:black;text-shadow:none !important;background:' . $color . ';border:none;border-radius:4px;-webkit-border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;width:99%;overflow:inherit;font-family:monospace !important;">';
}

/**
 * tag_pre_close()
 */
function tag_pre_close() {
	echo '</pre>';
}

/**
 * Afficher des informations de performance en bas de la page
 *  --- nombre de requêtes
 *  --- temps de génération de la page
 *
 * @link https://codex.wordpress.org/Function_Reference/timer_stop
 */
function gwp_wp_footer_debug() {
	if ( ! is_gilles_connecte() ) {
		return;
	}

	echo get_num_queries() . ' requêtes sql <br />';
	echo timer_stop( 0 ) . ' secondes<br />';

	// Afficher TOUTES les requêtes que la page a générées et pour ça, mettre define('SAVEQUERIES', true); dans wp-config.php
	if ( current_user_can( 'administrator' ) ) {
		global $wpdb;
		pre( $wpdb->queries );
	}
}

//add_filter( 'wp_footer', 'gwp_wp_footer_debug', 999 );
//add_filter( 'admin_footer', 'gwp_wp_footer_debug', 999 );


/**
 * Loggue avec error_log, en y ajoutant un préfixe utile pour chercher ensuite dans les logs.
 *
 * @author Gilles Dumas <circusmind@gmail.com>
 * @since  20150225
 * @link   http://php.net/manual/fr/function.error-log.php
 */
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

	/**
	 * Car des fois $msg contient des caractères NUL et que dans ce cas-là,
	 * la fonction error_log() s'arrête à ce caractère-là.
	 */
	$msg = ( string ) $msg;

	$new_str = '';
	for ( $i = 0; $i < strlen( $msg ); $i++ ) {
		if ( ord( $msg[$i] ) != 0 ) {
			$new_str .= $msg[$i];
		}
		else {
			$new_str .= ' ';
		}
	}
	$msg = $new_str;

	// À ce niveau-là, $msg est obligatoirement de type string, et si la variable en entrée était un objet ou un tableau, elle a été sérialisée.
	if ( $is_object || $is_array ) {
		$href = add_query_arg( [
			'str'    => base64_encode( $msg ),
			'encode' => 'base64'
		], 'https://outils.perpi.bz/unserialize' );

		$msg = "<a target='_blank' href='$href' style='color:hotpink;'>$msg</a>";
	}

	/**
	 * Debug simple avec valeur de la variable (comportement par défaut).
	 */
	if ( 1 === $type_de_debug ) {
	}
	/**
	 * Debug avec nom, type et valeur de la variable.
	 */
	elseif ( 2 === $type_de_debug ) {
		$msg = "La variable <strong style='color:blue;'>\$msg</strong> est de type <strong style='color:blue;'>$type_de_la_variable</strong> et vaut : $msg";
	}

	error_log( 'GWP LOG - ' . $msg );
}
