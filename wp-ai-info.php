<?php
/*
Plugin Name: WP AI Info
Description: Insert blog posts automatically using IA.
Version: 1.0.0
Author: Gilles Dumas
Author URI: https://gillesdumas.com
*/

require 'vendor/autoload.php';
require_once( '.env.php' );
require_once( 'debug.php' );

use Carbon\Carbon;

new wp_ai_info;

/**
 * Main plugin class.
 */
class wp_ai_info
{

	// Clé de chiffrement (doit être une chaîne de 32 caractères pour AES-256)
	const ENCRYPTION_KEY = 'Y0AT8xhF8xAm0ZDAxThkuFkrsrMrnxzs';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [
			$this,
			'wp_ai_info_init'
		], 20 );
		add_action( 'admin_init', [
			$this,
			'wp_ai_info_register_settings'
		] );
		add_action( 'admin_menu', [
			$this,
			'wp_ai_info_menu_options'
		] );
	}

	/**
	 * Sanitize la valeur, puis chiffre l'option avant sauvegarde.
	 *
	 * @param string $input
	 * @return string
	 */
	public function sanitize_option( $input ) {
		$clean = sanitize_text_field( $input );
		return self::encrypt_value( $clean );
	}

	/**
	 * Chiffre une valeur avec AES-256-CBC.
	 *
	 * @param string $data
	 * @return string
	 */
	private static function encrypt_value( $data ) {
		$cipher_method = 'AES-256-CBC';
		$iv_length = openssl_cipher_iv_length( $cipher_method );
		$iv = openssl_random_pseudo_bytes( $iv_length );
		$encrypted = openssl_encrypt( $data, $cipher_method, self::ENCRYPTION_KEY, 0, $iv );
		// Stocke l'IV avec les données chiffrées, puis encode en base64.
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Appel à l'API OpenAI pour créer un article.
	 *
	 * @return void
	 */
	public function wp_ai_info_init() {

		// gwplog( 'wp_ai_info_init()' );

		if ( ! isset( $_GET['inserer-article-ai'] ) ) {
			return;
		}

		if ( isset( $GLOBALS['wp_ai_info_init_done'] ) ) {
			return;
		}

		Carbon::setLocale( 'fr' );
		$date = Carbon::now();
		$date_fr = $date->translatedFormat( 'l d F Y' );

		$encrypted_value = get_option( 'wp_ai_info_option' );
		$api_key = '';
		if ( ! empty( $encrypted_value ) ) {
			$api_key = self::decrypt_value( $encrypted_value );
		}

		$url_open_api_endpoint = "https://api.openai.com/v1/chat/completions";

		$data_content = "Écris un article sur l'actualité la plus importante pour la date du $date_fr .";
		$data_content .= " Renvoie le formaté en markdown et surtout utilise les balises Hn (h2, h3, h4) mais pas de H1.";

		// Configuration des données envoyées.
		$data = [
			"model"         => "gpt-4",
			"messages"      => [
				[
					"role"    => "system",
					"content" => "Tu es un rédacteur de blog qui écrit des articles informatifs détaillés et bien structurés d'environ mille mots."
				],
				[
					"role"    => "user",
					"content" => $data_content
				]
			],
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
			"function_call" => [ "name" => "create_blog_article" ],
			"temperature"   => 0.7,
			"max_tokens"    => 2000
		];

		$ch = curl_init( $url_open_api_endpoint );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			"Content-Type: application/json",
			"Authorization: Bearer $api_key"
		] );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );

		$response = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			echo json_encode( [ "error" => curl_error( $ch ) ] );
		}
		else {
			$response_data = json_decode( $response, true );
			// pre2( $response_data );

			// Extraire uniquement le contenu généré par OpenAI.
			if ( isset( $response_data['choices'][0]['message']['function_call']['arguments'] ) ) {
				$article_json = $response_data['choices'][0]['message']['function_call']['arguments'];

				gwplog( 'article_json = ' );
				gwplog( $article_json );

				if ( self::is_json( $article_json ) ) {
					$article = json_decode( $article_json );

					$Parsedown = new Parsedown();
					$post_content = $Parsedown->text( $article->content );

					$args_insert = [
						'post_title'   => $article->title,
						'post_content' => $post_content,
						'post_status'  => 'publish',
					];

					// https://developer.wordpress.org/reference/functions/wp_insert_post/
					$result_insert = wp_insert_post( $args_insert );
					pre( $result_insert );

					/**
					 * Les post_meta.
					 */
					update_post_meta( $result_insert, 'wp_ai_info_prompt', $data_content );
					update_post_meta( $result_insert, 'wp_ai_info_data', $data );
				}
				else {
					echo 'Le format de retour de "arguments" n\'est pas du json';
					// pre2( $article_json );
				}
			}
			else {
				gwplog( 'error : Aucune réponse valide de l\'API' );
			}
		}

		curl_close( $ch );
		$GLOBALS['wp_ai_info_init_done'] = true;
	}

	/**
	 * Déchiffre une valeur chiffrée précédemment avec AES-256-CBC.
	 *
	 * @param string $data
	 * @return string|false
	 */
	private static function decrypt_value( $data ) {
		$data = base64_decode( $data );
		$cipher_method = 'AES-256-CBC';
		$iv_length = openssl_cipher_iv_length( $cipher_method );
		$iv = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );
		return openssl_decrypt( $encrypted, $cipher_method, self::ENCRYPTION_KEY, 0, $iv );
	}

	/**
	 * Vérifie si une chaîne est du JSON valide.
	 *
	 * @param string $string
	 * @return bool
	 */
	private static function is_json( $string ) {
		// Vérifier que $data est bien une chaîne
		if ( ! is_string( $string ) ) {
			return false;
		}
		// Tente de décoder la chaîne JSON
		json_decode( $string );

		// Vérifie s'il y a eu une erreur lors du décodage
		return ( json_last_error() === JSON_ERROR_NONE );
	}

	/**
	 * Ajout du menu dans l'administration.
	 *
	 * @return void
	 */
	public function wp_ai_info_menu_options() {
		add_options_page( 'Settings - WP AI INFO', // Titre de la page
			'WP AI Info',           // Titre du menu
			'manage_options',       // Capacité requise
			'wp_ai_info_options',   // Slug de la page
			[
				$this,
				'wp_ai_info_options_page'
			]   // Fonction callback
		);
	}

	/**
	 * Fonction de callback pour afficher le contenu de la page d'options.
	 *
	 * @return void
	 */
	public function wp_ai_info_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Vous n\'avez pas les permissions suffisantes pour accéder à cette page.' );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP AI INFO - Settings', 'mon-plugin-textdomain' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wp_ai_info_options_group' );
				do_settings_sections( 'wp_ai_info_options' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enregistrement des réglages, sections et champs.
	 *
	 * @return void
	 */
	public function wp_ai_info_register_settings() {
		// Enregistrement du réglage avec callback de sanitization.
		register_setting( 'wp_ai_info_options_group', 'wp_ai_info_option', [
			'sanitize_callback' => [
				$this,
				'sanitize_option'
			]
		] );

		// Ajout d'une section sur la page d'options.
		add_settings_section( 'wp_ai_info_section_id', 'Titre de la section', [
			$this,
			'wp_ai_info_section_callback'
		], 'wp_ai_info_options' );

		// Ajout d'un champ dans la section.
		add_settings_field( 'wp_ai_info_option', '<label for="wp_ai_info_option_0">OpenAI API KEY</label>', [
			$this,
			'wp_ai_info_option_callback'
		], 'wp_ai_info_options', 'wp_ai_info_section_id' );
	}

	/**
	 * Callback pour la section d'options.
	 *
	 * @return void
	 */
	public function wp_ai_info_section_callback() {
		echo '<p>Configurez les options de votre plugin ici.</p>';
	}

	/**
	 * Callback pour le champ de l'option.
	 *
	 * @return void
	 */
	public function wp_ai_info_option_callback() {
		$encrypted_value = get_option( 'wp_ai_info_option' );
		$value = '';
		if ( ! empty( $encrypted_value ) ) {
			$value = self::decrypt_value( $encrypted_value );
		}
		?>
		<input type="text" id="wp_ai_info_option_0" name="wp_ai_info_option" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<?php
	}
}
