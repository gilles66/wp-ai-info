<?php
/**
 * Les éléments de débogage.
 */

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

	if ( is_json( $p ) ) {
		$p = json_decode( $p );
	}

	if ( $htmlentities ) {
		$p = htmlentities( $p );
	}

	print_r( $p );
	tag_pre_close();
}

/**
 * Affiche une variable et arrête l'exécution du script.
 *
 * @param mixed  $p            La variable à afficher.
 * @param string $color        La couleur de fond.
 * @param bool   $htmlentities Convertit les caractères en entités HTML.
 * @return void
 */
function prexit( $p, $color = null, $htmlentities = false ) {
	pre( $p, $color, $htmlentities );
	exit;
}

/**
 * Affiche une variable avec var_dump formaté.
 *
 * @param mixed  $p            La variable à afficher.
 * @param string $color        La couleur de fond.
 * @param bool   $htmlentities Convertit les caractères en entités HTML.
 * @return void
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
 * Affiche une variable avec var_dump formaté et arrête l'exécution du script.
 *
 * @param mixed  $p     La variable à afficher.
 * @param string $color La couleur de fond.
 * @return void
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
 * Affiche une variable avec var_export formaté.
 *
 * @param mixed  $p            La variable à afficher.
 * @param string $color        La couleur de fond.
 * @param bool   $htmlentities Convertit les caractères en entités HTML.
 * @return void
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
 * Affiche une variable avec var_export formaté et arrête l'exécution du script.
 *
 * @param mixed  $p     La variable à afficher.
 * @param string $color La couleur de fond.
 * @return void
 */
function prexit3( $p, $color = null ) {
    pre3( $p, $color );
    exit;
}

/**
 * Ouvre une balise <pre> avec styles pour l'affichage clair.
 *
 * @param string|null $color Couleur de fond.
 * @return void
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
 * Ferme la balise <pre>.
 *
 * @return void
 */
function tag_pre_close() {
	echo '</pre>';
}

/**
 * Loggue avec error_log, en y ajoutant un préfixe utile pour chercher ensuite dans les logs.
 *
 * @param mixed $msg Le message ou la variable à logger.
 * @return void
 *
 * @author Gilles Dumas <circusmind@gmail.com>
 * @since  20150225
 * @link   http://php.net/manual/fr/function.error-log.php
 */
function gwplog( $msg ) {

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

	error_log( 'WP-AI-INFO LOG - ' . $msg );
}
