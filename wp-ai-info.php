<?php
/**
 * Plugin Name: WP AI Info
 * Plugin URI: https://gillesdumas.com
 * Description: WP AI Info est un plugin WordPress permettant de créer et publier automatiquement des articles de blog en s’appuyant sur OpenAI.
 * Version: 1.0.1
 * Author: Gilles Dumas
 * Author URI: https://gillesdumas.com
 * Text Domain: wp-ai-info
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'WP_AI_INFO_VERSION', '1.0.1' );
define( 'WP_AI_INFO_PLUGIN_FILE', __FILE__ );
define( 'WP_AI_INFO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_AI_INFO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load dependencies.
require_once WP_AI_INFO_PLUGIN_DIR . 'Parsedown.php';
// Load debug helpers only in debug mode.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    require_once WP_AI_INFO_PLUGIN_DIR . 'debug.php';
}

// Activation/deactivation hooks.
register_activation_hook( WP_AI_INFO_PLUGIN_FILE, array( 'wp_ai_info', 'activate' ) );
register_deactivation_hook( WP_AI_INFO_PLUGIN_FILE, array( 'wp_ai_info', 'deactivate' ) );

// Initialize the plugin.
new wp_ai_info();

/**
 * Main plugin class.
 */
class wp_ai_info
{

    // Clé de chiffrement (doit être une chaîne de 32 caractères pour AES-256).
    const ENCRYPTION_KEY = 'Y0AT8xhF8xAm0ZDAxThkuFkrsrMrnxzs';

    // Se retrouvera dans l'url de la page.
    const SLUG_PAGE = 'wp-ai-info-options';

    // Le préfixe de toutes les options du plugin.
    const PREFIX_OPTION_NAME = 'wp_ai_info_';

    /**
     * Activation hook: initialize default options.
     */
    public static function activate() {
        if ( false === get_option( self::PREFIX_OPTION_NAME . 'option_api_key', false ) ) {
            add_option( self::PREFIX_OPTION_NAME . 'option_api_key', '' );
        }
        if ( false === get_option( self::PREFIX_OPTION_NAME . 'option_prompt', false ) ) {
            add_option( self::PREFIX_OPTION_NAME . 'option_prompt', '' );
        }
    }

    /**
     * Deactivation hook: no-op.
     */
    public static function deactivate() {
        // Nothing to do.
    }

    /**
     * Load plugin textdomain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-ai-info',
            false,
            dirname( plugin_basename( WP_AI_INFO_PLUGIN_FILE ) ) . '/languages'
        );
    }

    /**
     * Constructor.
     */
    public function __construct() {
        // Load plugin textdomain for translations.
        add_action( 'init', [ $this, 'load_textdomain' ] );

        add_action( 'admin_init', [
                $this,
                'wp_ai_info_register_settings'
        ] );

        add_action( 'admin_menu', [
                $this,
                'add_option_page'
        ] );

        add_action( 'add_meta_boxes', [
                $this,
                'wp_ai_info_add_metabox'
        ] );

        /**
         * Appel à openAI lorsque l'on enregistre le prompt.
         */
        add_action( 'update_option_wp_ai_info_option_prompt', [
                $this,
                'make_openai_call'
        ], 10, 3 );
    }

    /**
     * Makes a call to openAI API and inserts a WordPress post.
     *
     * @return null
     */
    public function make_openai_call() {
        gwplog( 'make_openai_call()' );

        if ( isset( $GLOBALS['wp_ai_info_init_done'] ) ) {
            return;
        }

        $timestamp_debut = time();

        $encrypted_value = get_option( self::PREFIX_OPTION_NAME . 'option_api_key' );
        $api_key = '';
        if ( ! empty( $encrypted_value ) ) {
            $api_key = self::decrypt_value( $encrypted_value );
        }

        $url_open_api_endpoint = "https://api.openai.com/v1/chat/completions";

        $prompt = get_option( self::PREFIX_OPTION_NAME . 'option_prompt' );
        $data_content = $prompt;
        $data_content .= "Renvoie le contenu de l'article formaté en markdown sans titre principal (h1) car il y en a déjà un sur ma page.";

        // Configuration des données envoyées.
        $data = [
                "model"                 => "gpt-4o",
                // "gpt-4o-mini"
                "messages"              => [
                        [
                                "role"    => "system",
                                "content" => "Tu es un rédacteur de blog qui écrit des articles informatifs détaillés et bien structurés d'environ mille mots."
                        ],
                        [
                                "role"    => "user",
                                "content" => $data_content
                        ]
                ],
                "functions"             => [
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
                "function_call"         => [ "name" => "create_blog_article" ],
                "temperature"           => 0.1,
                // https://platform.openai.com/docs/api-reference/chat/create#chat-create-temperature
                // "max_tokens"    => 2000 // https://platform.openai.com/docs/api-reference/chat/create#chat-create-max_tokens
                "max_completion_tokens" => 2000
        ];

        // Prepare and send request via WP HTTP API.
        $response = wp_remote_post(
            $url_open_api_endpoint,
            array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'body'    => wp_json_encode( $data ),
                'timeout' => 60,
            )
        );
        if ( is_wp_error( $response ) ) {
            add_settings_error(
                self::PREFIX_OPTION_NAME . 'option_prompt',
                'option-prompt-error',
                esc_html__( 'Erreur de requête OpenAI : ', 'wp-ai-info' ) . $response->get_error_message(),
                'error'
            );
            return;
        }
        $response_body = wp_remote_retrieve_body( $response );
        $response_data = json_decode( $response_body, true );
            // pre2( $response_data );

            // Extraire uniquement le contenu généré par OpenAI.
            if ( isset( $response_data['choices'][0]['message']['function_call']['arguments'] ) ) {
                $article_json = $response_data['choices'][0]['message']['function_call']['arguments'];

                gwplog( 'article_json = ' );
                gwplog( $article_json );

                if ( $this->is_json( $article_json ) ) {
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
                    // pre( $result_insert );

                    if ( is_integer( $result_insert ) ) {
                        /**
                         * Les post_meta.
                         */
                        update_post_meta( $result_insert, 'wp_ai_info_prompt', $data_content );
                        update_post_meta( $result_insert, 'wp_ai_info_data', $data );
                        update_post_meta( $result_insert, 'wp_ai_info_openai_response', $response );
                        update_post_meta( $result_insert, 'wp_ai_info_temps_execution', time() - $timestamp_debut );

                        /**
                         * Personnaliser le message de succès qui vaut par défaut "Settings saved".
                         */
                        $msg = 'Article <a target="_blank" href="' . get_permalink( $result_insert ) . '">' . get_post_field( 'post_title', $result_insert ) . '</a> créé.';
                        add_settings_error( self::PREFIX_OPTION_NAME . 'option_prompt', 'option-prompt-success', $msg, 'success' );
                    }
                    else {
                        $msg = 'L\'article n\'a pas pu être inséré !';
                        add_settings_error( self::PREFIX_OPTION_NAME . 'option_prompt', 'option-prompt-error', $msg );
                    }
                }
                else {
                    echo 'Le format de retour de "arguments" n\'est pas du json';
                    // pre2( $article_json );
                }
            }
            else {
                gwplog( 'error : Aucune réponse valide de l\'API' );
                gwplog( $response_data );
                if ( is_array( $response_data ) && isset( $response_data['error'] ) ) {
                    add_settings_error( self::PREFIX_OPTION_NAME . 'option_prompt', 'option-prompt-error', $response_data['error']['message'] );
                }
            }

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
    private function is_json( $string ) {
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
     * Sanitize le prompt, puis la sauvegarde.
     *
     * @param string $input
     * @return string
     */
    public function sanitize_prompt( $input ) {
        return sanitize_text_field( $input );
    }

    /**
     * Ajout du menu dans l'administration.
     *
     * @return void
     */
    public function add_option_page() {
        add_options_page( 'Settings - WP AI INFO', // Titre de la page
                'WP AI Info',           // Titre du menu
                'manage_options',       // Capacité requise
                self::SLUG_PAGE,   // Slug de la page
                [
                        $this,
                        'generate_options_page'
                ]   // Fonction callback
        );
    }

    /**
     * Fonction de callback pour afficher le contenu de la page d'options.
     *
     * @return void
     */
    public function generate_options_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Vous n\'avez pas les permissions suffisantes pour accéder à cette page.' );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WP AI INFO', 'wp-ai-info' ); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo self::SLUG_PAGE; ?>&tab=prompt" class="nav-tab <?php echo ( ! isset( $_GET['tab'] ) || $_GET['tab'] == 'prompt' ) ? 'nav-tab-active' : ''; ?>">Génération d'article</a>
                <a href="?page=<?php echo self::SLUG_PAGE; ?>&tab=general" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] == 'general' ) ? 'nav-tab-active' : ''; ?>">Settings</a>
                <a href="?page=<?php echo self::SLUG_PAGE; ?>&tab=image" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] == 'image' ) ? 'nav-tab-active' : ''; ?>">Image</a>
            </h2>
            <?php
            $tab = $_GET['tab'] ?? 'prompt';
            if ( $tab == 'general' ) {
                $this->display_general_settings();
            }
            elseif ( $tab == 'prompt' ) {
                $this->display_prompt_settings();
            }
            elseif ( $tab == 'image' ) {
                $this->display_image_settings();
            }
            ?>
        </div>
        <?php
    }

    public function display_general_settings() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wp_ai_info_settings_group' );
            do_settings_sections( 'wp_ai_info_settings_page' );
            submit_button();
            ?>
        </form>
        <?php
    }

    public function display_prompt_settings() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wp_ai_info_prompt_group' );
            do_settings_sections( 'wp_ai_info_prompt_page' );
            submit_button( 'Générer l\'article' );
            ?>
        </form>
        <p>La génération d'un article via appel à l'API de OpenAI prend généralement entre 10 et 20 secondes.</p>
        <p>Si vous validez la génération de l'article sans avoir modifié le prompt alors rien ne se passera.</p>
        <?php
    }

    /**
     * Affiche les réglages pour la récupération d'informations d'une image.
     */
    public function display_image_settings() {
        if ( isset( $_POST['submit_image'] ) && check_admin_referer( 'wp_ai_info_image_action', 'wp_ai_info_image_nonce' ) ) {
            $attachment_id = intval( $_POST['attachment_id'] );
            gwplog( '$attachment_id =' . $attachment_id );
            if ( ! $attachment_id ) {
                add_settings_error( 'wp_ai_info_image', 'invalid_id', 'Merci de fournir un ID d\'image valide.', 'error' );
            } else {
                $file_path = get_attached_file( $attachment_id );
                gwplog( '$file_path =' . $file_path );
                if ( ! $file_path || ! file_exists( $file_path ) ) {
                    add_settings_error( 'wp_ai_info_image', 'invalid_id', 'Attachment ID invalide ou fichier introuvable.', 'error' );
                } else {
                    // Générer une miniature légère pour limiter le poids
                    $file_to_encode = $file_path;
                    $editor = wp_get_image_editor( $file_path );
                    if ( ! is_wp_error( $editor ) ) {
                        if ( method_exists( $editor, 'set_quality' ) ) {
                            $editor->set_quality( 30 );
                        }
                        $editor->resize( 128, 128, false );
                        $saved = $editor->save();
                        if ( ! is_wp_error( $saved ) && ! empty( $saved['path'] ) ) {
                            $file_to_encode = $saved['path'];
                        }
                    }
                    gwplog( '$file_to_encode = ' . $file_to_encode );

                    // Encodage base64
                    $file_content = file_get_contents( $file_to_encode );
                    $mime_type = wp_check_filetype( $file_to_encode )['type'] ?? 'image/jpeg';
                    $base64 = base64_encode( $file_content );
                    $data_uri = 'data:' . $mime_type . ';base64,' . $base64;

                    if ( isset( $saved['path'] ) && $file_to_encode !== $file_path ) {
                        @unlink( $file_to_encode );
                    }

                    // Clé API
                    $encrypted_value = get_option( self::PREFIX_OPTION_NAME . 'option_api_key' );
                    $api_key = '';
                    if ( ! empty( $encrypted_value ) ) {
                        $api_key = self::decrypt_value( $encrypted_value );
                    }

                    // Prompt multimodal
                    $messages = [
                            [
                                    "role" => "user",
                                    "content" => [
                                            [
                                                    "type" => "text",
                                                    "text" => "Décris cette image en JSON avec les champs suivants :
                                - title : un titre court en français
                                - description : une description factuelle en une phrase
                                - caption : une légende concise
                                - alt : un texte alternatif simple
                                Réponds uniquement avec du JSON valide."
                                            ],
                                            [
                                                    "type" => "image_url",
                                                    "image_url" => [
                                                            "url" => $data_uri
                                                    ]
                                            ]
                                    ]
                            ]
                    ];

                    $payload = [
                            "model"       => "gpt-4o-mini",
                            "messages"    => $messages,
                            "temperature" => 0.0,
                            "max_tokens"  => 200,
                            "response_format" => [
                                    "type" => "json_schema",
                                    "json_schema" => [
                                            "name" => "image_metadata",
                                            "schema" => [
                                                    "type" => "object",
                                                    "properties" => [
                                                            "title" => ["type" => "string"],
                                                            "description" => ["type" => "string"],
                                                            "caption" => ["type" => "string"],
                                                            "alt" => ["type" => "string"]
                                                    ],
                                                    "required" => ["title","description","caption","alt"],
                                                    "additionalProperties" => false
                                            ]
                                    ]
                            ]
                    ];


                    // Send request via WP HTTP API.
                    $response = wp_remote_post(
                        'https://api.openai.com/v1/chat/completions',
                        array(
                            'headers' => array(
                                'Content-Type'  => 'application/json',
                                'Authorization' => 'Bearer ' . $api_key,
                            ),
                            'body'    => wp_json_encode( $payload ),
                            'timeout' => 60,
                        )
                    );
                    if ( is_wp_error( $response ) ) {
                        add_settings_error( 'wp_ai_info_image', 'api_error', $response->get_error_message(), 'error' );
                    } else {
                        $response_body = wp_remote_retrieve_body( $response );
                        $response_data = json_decode( $response_body, true );
                        if ( isset( $response_data['choices'][0]['message']['content'] ) ) {
                            $json_content = trim( $response_data['choices'][0]['message']['content'] );

                            if ( $this->is_json( $json_content ) ) {
                                $data = json_decode( $json_content, true );

                                $titre      = $data['title'] ?? 'Image';
                                $description= $data['description'] ?? '';
                                $legende    = $data['caption'] ?? '';
                                $alt_text   = $data['alt'] ?? '';

                                // Mise à jour de la pièce jointe
                                $result = wp_update_post( [
                                        'ID'           => $attachment_id,
                                        'post_title'   => $titre,
                                        'post_content' => $description,
                                        'post_excerpt' => $legende,
                                ], true );

                                if ( is_wp_error( $result ) ) {
                                    add_settings_error( 'wp_ai_info_image', 'update_failed', $result->get_error_message(), 'error' );
                                } else {
                                    update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );

                                    add_settings_error(
                                            'wp_ai_info_image',
                                            'update_success',
                                            'Titre, description, légende et texte alternatif mis à jour pour l’ID ' . $attachment_id . '.',
                                            'success'
                                    );
                                }
                            } else {
                                add_settings_error( 'wp_ai_info_image', 'json_error', 'La réponse de l’IA n’est pas du JSON valide.', 'error' );
                                gwplog( $json_content );
                            }
                        } elseif ( isset( $response_data['error'] ) ) {
                            add_settings_error( 'wp_ai_info_image', 'api_error', $response_data['error']['message'], 'error' );
                        } else {
                            add_settings_error( 'wp_ai_info_image', 'api_error', 'Réponse invalide de l\'API.', 'error' );
                            gwplog($response_data);
                        }
                    }
                }
            }
        }
        settings_errors( 'wp_ai_info_image' );
        ?>
        <form method="post">
            <?php wp_nonce_field( 'wp_ai_info_image_action', 'wp_ai_info_image_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="attachment_id">Attachment ID</label></th>
                    <td><input type="number" name="attachment_id" id="attachment_id" class="small-text" required /></td>
                </tr>
            </table>
            <?php submit_button( 'Get Image Info', 'primary', 'submit_image' ); ?>
        </form>
        <?php
    }


    /**
     * Enregistrement des réglages, sections et champs.
     *
     * @return void
     */
    public function wp_ai_info_register_settings() {

        /**
         * Le champ api key.
         */

        // Enregistrement du réglage avec callback de sanitization.
        register_setting( 'wp_ai_info_settings_group', self::PREFIX_OPTION_NAME . 'option_api_key', [
                'sanitize_callback' => [
                        $this,
                        'sanitize_option'
                ]
        ] );

        // Ajout d'une section sur la page d'options.
        add_settings_section( 'section_id_settings', __( 'Settings', 'wp-ai-info' ), [
            $this,
            'display_section_apikey'
        ], 'wp_ai_info_settings_page' );

        // Ajout d'un champ dans la section.
        add_settings_field(
            'wp_ai_info_option',
            '<label for="option_api_key">' . esc_html__( 'OpenAI API KEY', 'wp-ai-info' ) . '</label>',
            [ $this, 'display_input_apikey' ],
            'wp_ai_info_settings_page',
            'section_id_settings'
        );

        /**
         * Le champ prompt.
         */

        // Enregistrement du réglage avec callback de sanitization.
        register_setting( 'wp_ai_info_prompt_group', self::PREFIX_OPTION_NAME . 'option_prompt', [
                'sanitize_callback' => [
                        $this,
                        'sanitize_prompt'
                ]
        ] );

        // Ajout d'une section sur la page d'options.
        add_settings_section( 'section_id_prompt', __( 'Génération d\'un article', 'wp-ai-info' ), [
            $this,
            'display_section_prompt'
        ], 'wp_ai_info_prompt_page' );

        add_settings_field(
            'my_prompt',
            '<label for="option_prompt">' . esc_html__( 'Prompt', 'wp-ai-info' ) . '</label>',
            [ $this, 'display_input_prompt' ],
            'wp_ai_info_prompt_page',
            'section_id_prompt'
        );
    }

    /**
     * Callback pour la section d'options.
     *
     * @return void
     */
    public function display_section_apikey() {
        echo '<p>Configurez les options de votre plugin ici.</p>';
    }

    /**
     * Callback pour la section d'options.
     *
     * @return void
     */
    public function display_section_prompt() {
        echo '<p>Saisissez votre prompt ci-dessous.</p>';
    }

    /**
     * Callback pour le champ de l'option dans la page de settings en BO.
     *
     * @return void
     */
    public function display_input_apikey() {
        $encrypted_value = get_option( self::PREFIX_OPTION_NAME . 'option_api_key' );
        $value = '';
        if ( ! empty( $encrypted_value ) ) {
            $value = self::decrypt_value( $encrypted_value );
        }
        ?>
        <input type="text" id="option_api_key" name="<?php echo self::PREFIX_OPTION_NAME; ?>option_api_key" value="<?php echo esc_attr( $value ); ?>" class="large-text" />
        <?php
    }

    /**
     * Callback pour le champ de l'option dans la page de settings en BO.
     *
     * @return void
     */
    public function display_input_prompt() {
        $value = get_option( self::PREFIX_OPTION_NAME . 'option_prompt' );
        ?>
        <input type="hidden" name="inserer-article-ai" value="1" />
        <textarea id="option_prompt" name="<?php echo self::PREFIX_OPTION_NAME; ?>option_prompt" class="large-text"><?php echo sanitize_textarea_field( $value ); ?></textarea>
        <?php
    }

    public function wp_ai_info_add_metabox() {
        add_meta_box( 'wp_ai_info_metabox', 'Informations WP AI Info', [
                $this,
                'wp_ai_info_metabox_callback'
        ], 'post', 'normal', 'high' );
    }

    public function wp_ai_info_metabox_callback( $post ) {
        $meta_values = get_post_meta( $post->ID );
        echo '<table class="form-table">';
        foreach ( $meta_values as $key => $array_value ) {
            // Non utilisation de la fonction str_starts_with() car elle est apparue en PHP8.
            if ( strpos( $key, 'wp_ai_info_' ) === 0 ) {
                echo '<tr>';
                echo '<th scope="row" style="padding-top:30px;">';
                echo esc_html( ucfirst( str_replace( 'wp_ai_info_', '', $key ) ) );
                echo '</th>';
                echo '<td>';
                if ( is_array( $array_value ) ) {
                    $value = $array_value[0];
                    if ( 'wp_ai_info_temps_execution' == $key ) {
                        $value .= ' secondes';
                    }

                    echo '<pre style="background-color:aliceblue;white-space:pre-wrap;padding:15px;font-size:13px;border-radius:5px;">';
                    print_r( is_serialized( $value ) ? unserialize( $value ) : $value );
                    echo '</pre>';
                }
                echo '</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
    }
}
