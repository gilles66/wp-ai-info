<?php


// phpinfo();
// exit;

/**
 * https://chatgpt.com/c/67cdb420-8bec-8001-a08b-b9348f3dc635
 */

$url = 'https://api.openai.com/v1/models'; // Remplace par ton URL
// $data = [
// 	'nom'   => 'Gilles',
// 	'email' => 'gilles@example.com'
// ];

$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
// curl_setopt( $ch, CURLOPT_POST, true );
// curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );

/**
 * https://platform.openai.com/docs/api-reference/authentication
 */

/**
 * Les headers.
 */
curl_setopt( $ch, CURLOPT_HTTPHEADER, [
	'Authorization: Bearer ' . OPENAI_API_KEY_PLUGIN_AI_INFO,
	'OpenAI-Organization: ' . 'org-3vBKJLHscKCcBtsHMPS8ThBs',
] );
$response = curl_exec( $ch );
pre( $response );

if ( curl_errno( $ch ) ) {
	echo 'Erreur cURL : ' . curl_error( $ch );
}
// else {
// 	echo 'Réponse : ' . $response;
// }

curl_close( $ch );

exit;