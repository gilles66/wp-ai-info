<?php
/**
 * https://chatgpt.com/c/67cdbc2c-9ba0-8001-8979-ec96fc22d130
 */


// Remplace par ta clé API OpenAI
$api_key = OPENAI_API_KEY_PLUGIN_AI_INFO;

// URL de l'API OpenAI
$url = "https://api.openai.com/v1/chat/completions";

// Configuration des données envoyées
$data = [
	"model"       => "gpt-4",
	// ou "gpt-3.5-turbo"
	"messages"    => [
		[
			"role"    => "system",
			"content" => "Tu es un assistant utile."
		],
		[
			"role"    => "user",
			"content" => "Donne-moi une blague en JSON."
		]
	],
	"temperature" => 0.9
];

// Initialiser cURL
$ch = curl_init( $url );

// Configurer l'option cURL
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
	echo 'Erreur cURL : ' . curl_error( $ch );
}
else {
	// Afficher le JSON retourné par OpenAI
	header( 'Content-Type: application/json' );
	echo $response;
}

// Fermer la connexion cURL
curl_close( $ch );
