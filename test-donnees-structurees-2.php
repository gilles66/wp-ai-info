<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

// Définir la locale en français
Carbon::setLocale( 'fr' );
$date = Carbon::now();
$date_fr = $date->translatedFormat( 'l d F Y' );
// die();

// Remplace par ta clé API OpenAI
$api_key = OPENAI_API_KEY_PLUGIN_AI_INFO;

// URL de l'API OpenAI
$url = "https://api.openai.com/v1/chat/completions";

/**
 * Comment utiliser le paramètre response_format :
 * https://platform.openai.com/docs/guides/structured-outputs#how-to-use
 */

$data_content = "Écris un article sur l'actualité la plus importante pour la date du $date_fr .";
$data_content .= "Renvoie le formaté en markdown et surtout utilise les balises Hn (h2, h3, h4) mais pas de H1.";

// Configuration des données envoyées
$data = [
	"model"         => "gpt-4o",
	// modèle avec support des appels de fonctions
	"messages"      => [
		[
			"role"    => "system",
			"content" => "Tu es un rédacteur de blog qui écrit des articles informatifs et bien structurés d'environ mille mots."
		],
		[
			"role"    => "user",
			"content" => $data_content
		]
	],
	// Définition de la fonction pour structurer la sortie
	"functions"     => [
		[
			"name"        => "create_blog_article",
			"description" => "Crée un article de blog en français avec un titre et un contenu.",
			"parameters"  => [
				"type"       => "object",
				"properties" => [
					"title"   => [
						"type"        => "string",
						"description" => "Le titre de l'article"
					],
					"content" => [
						"type"        => "string",
						"description" => "Le contenu complet de l'article"
					]
				],
				"required"   => [
					"title",
					"content"
				]
			]
		]
	],
	// Indiquer à l'API de répondre via l'appel de la fonction définie
	"function_call" => [ "name" => "create_blog_article" ],
	"temperature"   => 0.7,
	"max_tokens"    => 2000
	// ajustez selon vos besoins
];

$ch = curl_init( $url );

curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_POST, true );
curl_setopt( $ch, CURLOPT_HTTPHEADER, [
	"Content-Type: application/json",
	"Authorization: Bearer $api_key"
] );
curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );

// Exécuter la requête et récupérer la réponse
$response = curl_exec( $ch );

// Vérifier s'il y a une erreur
if ( curl_errno( $ch ) ) {
	echo json_encode( [ "error" => curl_error( $ch ) ] );
}
else {
	// Décoder la réponse JSON
	$response_data = json_decode( $response, true );
	pre2( $response_data );

	// Extraire uniquement le contenu généré par OpenAI
	if ( isset( $response_data['choices'][0]['message']['function_call']['arguments'] ) ) {
		$article_json = $response_data['choices'][0]['message']['function_call']['arguments'];

		if ( is_json( $article_json ) ) {
			$article = json_decode( $article_json );
			$args_insert = [
				'post_title'   => $article->title,
				'post_content' => $article->content,
				'post_status'  => 'publish',
			];

			// https://developer.wordpress.org/reference/functions/wp_insert_post/
			$result_insert = wp_insert_post( $args_insert );
			pre( $result_insert );
		}
		else {
			echo 'Le format de retour de "arguments" n\'est pas du json';
			// pre2( $article_json );
		}
	}
	else {
		echo json_encode( [ "error" => "Aucune réponse valide de l'API" ] );
	}
}

// Fermer la connexion cURL
curl_close( $ch );
