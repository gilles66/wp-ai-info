<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

// Définir la locale en français
Carbon::setLocale( 'fr' );
$date = Carbon::now();
// die( $date );

// Remplace par ta clé API OpenAI
$api_key = OPENAI_API_KEY_PLUGIN_AI_INFO;

// URL de l'API OpenAI
$url = "https://api.openai.com/v1/chat/completions";


// Configuration des données envoyées
$data = [
	"model"       => "gpt-4o", // GPT-4-turbo, gpt-4o
	"messages"    => [
		[
			"role"    => "system",
			"content" => "Tu es un rédacteur de blog qui écrit des articles informatifs et bien structurés."
		],
		[
			"role"    => "user",
			"content" => "Écris un article sur l'actualité la plus importante pour la date du lundi 3 mars 2025. Retourne uniquement du JSON avec deux clés : \"titre\" et \"content\"."
		]
	],
	"temperature" => 0.7,
	"max_tokens"  => 800
];

/*
Attention, ce que j'ai écrit ci-dessous n'est pas vrai en utilisant le modèle gpt-4o.
---

Le retour de openAI est
{
  "titre": "Erreur de date",
  "content": "Désolé, je ne peux pas fournir d'informations sur les événements futurs, y compris ceux de 2025. Mon dernier accès à l'information remonte à décembre 2023. Pour des informations à jour, veuillez consulter une source d'actualités actuelle."
}
Donc avec l'api je ne peux pas avoir d'informations récentes !
Zut !

Et pourtant l'article http://ai-info.localhost/index.php/2025/03/09/crise-energetique-en-france-mesures-durgence-et-perspectives/
du 9 mars 2025 fait référence à 2025.
J'avais inséré cet article avec l'utilisation de la class trouvée sur GitHub, donc je ne comprends pas.

J'apprends que https://newsapi.org/ peut fournir des informations via API mais coùte cher :
min $449 per month, billed monthly.
 */

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
	echo json_encode( [ "error" => curl_error( $ch ) ] );
}
else {
	// Décoder la réponse JSON
	$response_data = json_decode( $response, true );

	// Extraire uniquement le contenu généré par OpenAI
	if ( isset( $response_data['choices'][0]['message']['content'] ) ) {
		$article_json = $response_data['choices'][0]['message']['content'];
		header( 'Content-Type: application/json' );
		echo $article_json;
	}
	else {
		echo json_encode( [ "error" => "Aucune réponse valide de l'API" ] );
	}
}

// Fermer la connexion cURL
curl_close( $ch );
