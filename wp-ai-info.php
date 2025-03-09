<?php
/*
Plugin Name: WP AI Info
Description: Insert blog posts automatically using IA.
Version: 1.0.0
Author: Gilles Dumas
Author URI: https://gillesdumas.com
*/

require 'vendor/autoload.php';

use Carbon\Carbon;

// Définir la locale en français
Carbon::setLocale( 'fr' );

//require_once( '_debug.php' );
require_once( '.env.php' );
require_once( 'vendor/autoload.php' );
require_once( 'debug.php' );

/**
 * Développé par moi.
 */
// require_once( 'open-ai-php-client.php' );
// exit;

function ai_init() {

	if ( ! isset( $_GET['inserer-article-ai'] ) ) {
		return;
	}

	if ( isset( $GLOBALS['init_deja_fait'] ) ) {
		return;
	}

	require_once ('test-donnees-structurees.php');
	// phpinfo();
	exit;

	$client = OpenAI::client( OPENAI_API_KEY_PLUGIN_AI_INFO );

	// Obtenir la date actuelle
	$date = Carbon::now();

	// Afficher la date formatée
	echo $date->isoFormat( 'dddd D MMMM YYYY' );

	$prompt = 'Écris un article optimisé pour le SEO concernant l\'actualité la plus importante en France  pour la date du ' . $date->isoFormat( 'dddd D MMMM YYYY' ) . '. ';
	$prompt .= 'Mets des balises html pour la sémantique, pour que je puisse l\'insérer dans un composant d\'une page web. Mais ne mets pas de titre h1 et ne ré-écris pas le titre dans le contenu de l\'article. Tu peux mettre des titre h2 et h3 etc... ';
	$prompt .= 'Renvoie du format json avec une entrée "title" pour le titre de l\'article et une entrée "content" pour le contenu de l\'article. Tu dois absolument renvoyer du format json car sinon je ne pourrai pas exploiter le résultat. ';
	$prompt .= 'Ton retour doit donc comporter en premier caractère une accolade ouvrante et en dernier caractère une accolade fermante. Merci d\'avance. ';

	gwplog( '$prompt = ' );
	gwplog( $prompt );

	$result = $client->chat()->create( [
		//		'model' => 'gpt-4',
		'model'    => 'gpt-4o',
		// Liste des models https://platform.openai.com/docs/models.
		'messages' => [
			[ 'role'    => 'user',
			  'content' => $prompt
			],
		],
	] );

	$reponse = $result->choices[0]->message->content; // Hello! How can I assist you today?

	gwplog( 'reponse = ' );
	gwplog( $reponse );

	$wordpress_post = [
		'post_title'   => 'RAPPORT AI - ' . date( 'Ymd-H-i-s' ),
		'post_content' => $prompt . '<hr />' . $reponse,
		'post_status'  => 'publish',
		'post_author'  => 1,
		'post_type'    => 'rapport_appel_api_ai'
	];
	$rapport_id = wp_insert_post( $wordpress_post );
	$post_array = [ 'ID' => $rapport_id ];

	if ( json_decode( $reponse ) ) {
		$a_reponse = json_decode( $reponse );
		pre2( $a_reponse, 'lightgreen' );
		if ( isset( $a_reponse->title ) && isset( $a_reponse->content ) ) {
			//		if ( isset( $a_reponse['title'] ) && isset( $a_reponse['content'] ) && isset( $a_reponse['date'] ) ) {
			$wordpress_post = [
				'post_title'   => $a_reponse->title,
				'post_content' => $a_reponse->content,
				'post_status'  => 'publish',
				'post_author'  => 1,
				'post_type'    => 'post'
			];
			wp_insert_post( $wordpress_post );
			pre( 'Article inséré !' );
			gwplog( 'Article inséré !' );

			$post_array['is_json'] = 1;
			wp_update_post( $post_array );
		}
	}
	else {
		pre( 'le retour n\'est pas au format json.' );
		gwplog( 'le retour n\'est pas au format json.' );
		pre2( $reponse );

		$post_array['is_json'] = 0;
		wp_update_post( $post_array );

	}

	$GLOBALS['init_deja_fait'] = true;

}

add_action( 'init', 'ai_init', 20, 1 );
