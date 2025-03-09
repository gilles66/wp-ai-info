<?php
/**
 * Les éléments de debogage
 *
 * Dernière modification de ce fichier : 20210115
 */


if (function_exists('gwplog')) {
	return;
}

$GLOBALS['ip_autorisees'] = array(
	'83.156.180.79', // Viry Châtillon
	'78.216.63.36', // 66300
	'81.250.134.3' // APC
);

/**
 * Les actions filtrées sur IP
 */
if (in_array($_SERVER['REMOTE_ADDR'], $GLOBALS['ip_autorisees'])) {

	// Accepter la connexion quelque soit le mot de passe
	// add_filter('check_password', '__return_true');

	// // Affichage d'un bloc de debug
	// add_filter('wp_head', 'gwp_affiche_bloc_debug');
}


/**
 * Ne pas logguer les notices dans le wp-content/debug.log !
 *
 * @author Gilles Dumas <circusmind@gmail.com>
 * @since 20180910
 * @link  http://www.php.net/manual/en/function.error-reporting.php
 */
function gwp_dont_log_notices_callback() {
	error_reporting(E_ALL & ~E_NOTICE);
}
// add_action('init', 'gwp_dont_log_notices_callback');


/**
 * pre()
 */
function pre($p, $color=null, $htmlentities=false) {
	tag_pre_open($color);

	if ($htmlentities) {
		$p = htmlentities($p);
	}

	print_r($p);
	tag_pre_close();
}

/**
 * prexit()
 */
function prexit($p, $color=null, $htmlentities=false) {
	pre($p, $color, $htmlentities);
	exit;
}

/**
 * pre2()
 */
function pre2($p, $color=null, $htmlentities=false) {
	tag_pre_open($color);

	if ($htmlentities) {
		$p = htmlentities($p);
	}

	var_dump($p);
	tag_pre_close();
}

/**
 * prexit2()
 */
function prexit2($p, $color=null) {
	pre2($p, $color);
	exit;
}

/**
 * pre3()
 */
function pre3($p, $color=null, $htmlentities=false) {
	tag_pre_open($color);

	if ($htmlentities) {
		$p = htmlentities($p);
	}
	var_export($p);
	tag_pre_close();
}

/**
 * prexit3()
 */
function prexit3($p, $color=null) {
	pre3($p, $color);
	exit;
}

/**
 * tag_pre_open()
 */
function tag_pre_open($color) {
	// $color = ($color == null) ? '#77C1F9' : $color;
	$color = ($color == null) ? 'powderblue' : $color;

	$margin  = 'margin:10px;';
	$padding = 'padding:5px;';

	if (function_exists('is_admin')) {
		if (is_admin()) {
			$padding.= 'padding-left:235px;';
		}
	}

	echo '<pre style="box-sizing:border-box;'.$margin.$padding.'text-align:left;font-size:0.9em;color:black;text-shadow:none !important;background:'.$color.';border:1px black solid;border-radius:4px;-webkit-border-radius:4px;-moz-border-radius:4px;-webkit-border-radius:4px;width:99%;overflow:inherit;font-family:monospace !important;">';
	echo "\n";
}

/**
 * tag_pre_close()
 */
function tag_pre_close() {
	echo "\n";
	echo '</pre>';
}



/**
 * Loggue avec error_log, en y ajoutant un préfixe utile pour chercher ensuite dans les logs.
 * @author Gilles Dumas <circusmind@gmail.com>
 * @since 20150225
 * @link  http://php.net/manual/fr/function.error-log.php
 */
function gwp_log($msg) {

	if (is_object($msg)) {
		$msg = serialize($msg);
	}

	if (is_array($msg)) {
		$msg = serialize($msg);
	}

	if ($msg === true) {
		$msg = 'TRUE';
	}
	if ($msg === false) {
		$msg = 'FALSE';
	}

	/*
	Ceci car des fois $msg contient des caractères NUL et que dans ce cas là,
	la fonction error_log() s'arrête à ce caractère là.
	*/
	$msg = (string) $msg;

	$new_str = '';
	for($i=0; $i<strlen($msg); $i++) {
		if (ord($msg[$i]) != 0) {
			$new_str.= $msg[$i];
		}
		else {
			$new_str.= ' ';
		}
	}
	$msg = $new_str;

	error_log('GWP LOG - '.$msg);
}
/**
 * Un alias de la fonction précédente
 */
function gwplog($msg) {
	return gwp_log($msg);
}


/**
 * Vide le fichier wp-content/debug.log
 * @author Gilles Dumas <circusmind@gmail.com>
 * @since 20150726
 */
function gwp_vide_fichier_log() {
	$f = @fopen(WP_CONTENT_DIR.'/debug.log', 'r+');
	if ($f !== false) {
		ftruncate($f, 0);
		fclose($f);
	}
}
