<?php
/**
 * Plugin Name: AI Engine for WordPress: ChatGPT, GPT Content Generator
 * Description: Generate high-quality and engaging content with the help of the cutting-edge ChatGPT and GPT-3 technologies.
 * Version: 1.0.1
 * Author: LiquidThemes
 * Author URI: https://hub.liquid-themes.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lqdai
 * Domain Path: /languages
 * 
 * Liquid ChatGPT is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * Liquid ChatGPT is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'LQDAI_PATH', plugin_dir_path( __FILE__ ) );
define( 'LQDAI_URL', plugin_dir_url( __FILE__ ) );
define( 'LQDAI_VERSION', get_file_data( __FILE__, array('Version' => 'Version'), false)['Version']);

final class Liquid_ChatGPT {

	private static $_instance = null;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }

    public function __construct() {
        $this->init();
    }

	public function i18n() {
		load_plugin_textdomain( 'lqdai' );
	}

    function init(){

        $this->i18n();
        $this->hooks();
        $this->include();
        $this->options = get_option( 'lqdai_options' );

    }

    function hooks() {

        add_action( 'admin_enqueue_scripts', function() {

            wp_enqueue_script( 
                'lqdai-script',
                LQDAI_URL . 'assets/script.js',
                ['jquery'],
                null
            );
        
            wp_enqueue_style( 
                'lqdai-style',
                LQDAI_URL . 'assets/style.css',
                []
            );
        
        } );
        
        add_action( 'admin_footer', [$this, 'template'] );
        add_action( 'edit_form_after_title', [ $this, 'print_lqdai_button' ] );
        
        add_action( 'wp_ajax_lqdai_get_response', [$this, 'lqdai_get_response'] );
        add_action( 'wp_ajax_lqdai_add_log', [$this, 'lqdai_add_log'] );
        add_action( 'wp_ajax_lqdai_update_post', [$this, 'update_post'] );
        add_action( 'wp_ajax_lqdai_get_images', [$this, 'get_images_from_unsplash'] );

    }

    function include() {
        include_once LQDAI_PATH . 'include/page-options.php';
        include_once LQDAI_PATH . 'include/page-logs.php';
    }

    function print_lqdai_button() {

        global $current_screen;

        if ( $current_screen->id !== 'post' ) {
            return;
        }

        ?>
            <div class="lqdai-action components-button edit-post-fullscreen-mode-close lqdai-action-classic"><span class="dashicons dashicons-image-filter"></span> Liquid ChatGPT</div>
        <?php
    }

    function template() {

        global $current_screen;

        if ( $current_screen->id !== 'post' ) {
            return;
        }

        ?>
            <div class="lqdai-template">
                <div class="lqdai-template-wrapper">
                    <div class="lqdai-template-header">
                        <div class="components-button is-pressed has-icon logo"><span class="dashicons dashicons-image-filter"></span> Liquid ChatGPT</div>
                        <div class="components-button is-pressed has-icon lqdai-template--close"><span class="dashicons dashicons-no-alt"></span></div>
                    </div>
                    <div class="lqdai-template-content">
                        <form id="lqdai-form" class="lqdai-form" action="lqdai-action" method="post">

                            <div class="form-field">
                                <label for="operation"><?php esc_html_e( 'Operation:', 'lqdai' ); ?></label>
                                <select name="operation" id="operation" required>
                                    <option value="post"><?php esc_html_e( 'Post Generator', 'lqdai' ); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="prompt"><?php esc_html_e( 'Prompt:', 'lqdai' ); ?></label>
                                <input type="text" id="prompt" placeholder="wp site optimization" required>
                                <p class="description">
                                    <?php esc_html_e( 'Define the blog post subject. Examples: wordpress plugin installation', 'lqdai' ); ?> 
                                </p>
                            </div>

                            <div class="form-field options">
                                <p><?php esc_html_e( 'Select items to create:', 'lqdai' ); ?></p>
                                <input type="checkbox" id="image" name="options" value="image" checked>
                                <label for="image"><?php esc_html_e( 'Image', 'lqdai' ); ?></label>
                            </div>

                            <div class="form-field">
                                <label for="temperature"><?php esc_html_e( 'Temperature:', 'lqdai' ); ?></label>
                                <input type="number" name="temperature" id="temperature" value="0.4" min="0" max="1" step="0.1" required>
                                <p class="description"><?php esc_html_e( 'The temperature determines how greedy the generative model is.', 'lqdai' ); ?></p>
                            </div>

                            <div class="form-field">
                                <button type="submit" class="button button-primary">
                                    <div class="lds-ripple"><div></div><div></div></div>
                                    <span><?php esc_html_e( 'Generate', 'lqdai' ); ?></span>
                                </button>
                            </div>

                            <?php wp_nonce_field( 'lqdai-form-response', 'security' ); ?>
                        </form>

                        <!-- result -->
                        <form id="lqdai-form-result" class="lqdai-form" action="lqdai-result" method="post">
                            <h1 class="lqdai-form--title"><?php esc_html_e( 'Result', 'lqdai' ); ?></h1>
                            <div class="form-field">
                                <label for="title"><?php esc_html_e( 'Post Title:', 'lqdai' ); ?></label>
                                <input type="text" id="title" required>
                            </div>
                            
                            <div class="form-field">
                                <label for="content"><?php esc_html_e( 'Post Content:', 'lqdai' ); ?></label>
                                <textarea name="content" id="content" cols="30" rows="10" required></textarea>
                            </div>

                            <div class="form-field">
                                <label for="tags"><?php esc_html_e( 'Post Tags:', 'lqdai' ); ?></label>
                                <input type="text" id="tags" required>
                            </div>

                            <div class="form-field generated-images">
                                <p><?php esc_html_e( 'Select Featured Image:', 'lqdai'); ?></p>
                            </div>

                            <div class="form-field">
                                <p class="description"><?php esc_html_e( 'Please leave blank the fields you do not wish to import.', 'lqdai' ); ?></p>
                            </div>

                            <input type="hidden" name="post_id" id="post_id" value="<?php echo esc_attr( get_the_ID() ); ?>">

                            <div class="form-field">
                                <button type="submit" class="button button-primary">
                                    <div class="lds-ripple"><div></div><div></div></div>
                                    <span><?php esc_html_e('Insert Data (Override current Post Data)', 'lqdai'); ?></span>
                                </button>
                                <div class="description lqdai-recreate"><?php printf( '%s <u>%s</u>', __( "Didn't you like the result?", "lqdai" ), __("Recreate", "lqdai") ); ?></div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        <?php
    }

    function get_string_between( $string, $start, $end ){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    function lqdai_get_response() {

        check_ajax_referer( 'lqdai-form-response', 'security' );

        if ( empty( $_POST['prompt'] ) ) {
            wp_send_json( [
                'error' => true,
                'message' => __( 'Prompt is required!', 'lqdai' )
            ] );
        }
       
        if ( empty( $_POST['operation'] ) ) {
            wp_send_json( [
                'error' => true,
                'message' => __( 'Operation is required!', 'lqdai' )
            ] );
        }

        $operation = sanitize_text_field( $_POST['operation'] );
        $prompt_value = sanitize_text_field( $_POST['prompt'] );

        switch( $operation ) {
            case 'post':
                $prompt = "write blog post about {$prompt_value} with title, content and tags as json (content lenght: 300-500)";
                break;

        }

        $api_key = isset( $this->options['api_key'] ) ? $this->options['api_key'] : '';
        $model = isset( $this->options['model'] ) ? $this->options['model'] : 'text-davinci-003';
        $temperature = !empty($_POST['temperature']) ? (int) $_POST['temperature'] : 0.4;
        $max_tokens = isset( $this->options['max_tokens'] ) ? (int) $this->options['max_tokens'] : 2048;

        $endpoint = 'https://api.openai.com/v1/completions';

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $api_key"
            ),
            'body' => json_encode( array( 
                'prompt' => $prompt, 
                'temperature' => $temperature, 
                'max_tokens' => $max_tokens,
                'model' => $model
            ) ),
            'timeout' => 300,
        );

        $response = wp_remote_post( $endpoint, $args );
        
        if ( ! is_wp_error( $response ) ) {
            $response_body = json_decode( wp_remote_retrieve_body( $response ) );

            if ( isset( $response_body->error->message ) ) {
                wp_send_json( [
                    'error' => true,
                    'message' => $response_body->error->message
                ] );
            } else {

                $json = $response_body->choices[0]->text;

                if ( $response_body->choices[0]->finish_reason === 'length' ) {
                    wp_send_json( [
                        'error' => true,
                        'message' => __( 'Operation failed: Max Token value is not enougth for this prompt!', 'lqdai' ),
                    ] );
                }
                
                $title = $this->get_string_between($json, '"title": "', '",');
                $content = $this->get_string_between($json, '"content": "', '",');
                $tags = $this->get_string_between($json, '"tags": [', ']');
                $tags = str_replace(['"', "\n", "        "], ['', '', ''], $tags);

                $image_value = sanitize_text_field( $_POST['image'] );
                $image = !empty( $image_value ) ? $image_value : 'false';

                $total_tokens = sprintf( 'This operation spend %s tokens', $response_body->usage->total_tokens );

                wp_send_json( [
                    'message' => 'Generated!',
                    'response_body' => $response_body,
                    'post' => [
                        'title' => $title,
                        'content' => $content,
                        'tags' => $tags,
                        'image' => $image
                    ],
                    'total_tokens' => $total_tokens,
                ] );
                
            }
        } else {
            wp_send_json( [
                'error' => true,
                'message' => $response->get_error_message()
            ] );
        }

    }

    function update_post() {

        if ( empty( $posts = $_POST['posts'] ) ) {
            wp_send_json( [
                'error' => true,
                'message' => __( 'Data is null!', 'lqdai' ),
            ] );
        }

        $args = [
            'ID'            => $posts['post_id'],
            'post_title'    => $posts['title'],
            'post_content'  => $posts['content'],
            'post_status'   => 'draft',
        ];

        $update_post = wp_update_post( $args );

        if ( is_wp_error( $update_post ) ) {
            wp_send_json( [
                'error' => true,
                'message' => $update_post->get_error_messages()
            ] );
        } else {
            wp_set_post_tags( $posts['post_id'], $posts['tags'], false );

            if ( !empty( $posts['image'] ) ) {
                $this->insert_image( $posts['post_id'], $posts['image'] );
            }
           
            wp_send_json( [
                'message' => __( 'Post Updated. Post ID:' . $posts['post_id'], 'lqdai' ),
                'posts' => $posts,
                'redirect' => admin_url( 'post.php?post=' . $update_post . '&action=edit' )
            ] );
        }
        
    }

    function get_images_from_unsplash() {

        $queries = explode( ',', sanitize_text_field( $_POST['query'] ) );
        //shuffle($queries);
        $query = ltrim($queries[0]);

        $api_key = isset( $this->options['api_key_unsplash'] ) ? $this->options['api_key_unsplash'] : '';

        if ( empty( $api_key ) ) {

            wp_send_json( [
                //'error' => true,
                'message' => __( 'Unsplash API Key is missing! Go to the settings and add your API key', 'lqdai' ),
            ] );
        }

        $api_params = [
            'client_id' => $api_key,
            'query' => $query,
            'per_page' => 4
        ];
    
        // https://unsplash.com/documentation
        $response = wp_remote_get( 
            add_query_arg( $api_params, "https://api.unsplash.com/search/photos" ),
            array( 'timeout' => 15 )
        );
    
        if ( ! is_wp_error( $response ) ) {
            $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( $error = $response_body['errors'][0] ) {
                wp_send_json( [
                    'error' => true,
                    'message' => $error
                ] );
            }

            $response_body = $response_body['results'];
            $out = '<div class="generated-images-wrapper">';
                foreach ( $response_body as $key => $images ) { 
                $out .= '<div class="generated-images-option">';
                $out .= sprintf( 
                        '<input type="radio" id="%s" name="generated-image" value="%s" %s>',
                        esc_attr( 'generated-image-' . $key ),
                        esc_url( $images['urls']['full'] ),
                        $key === 0 ? 'checked' : ''
                    );
                $out .= sprintf( '<label for="%s"><img src="%s">%s %s</label></div>', esc_attr( 'generated-image-' . $key ), esc_url( $images['urls']['full'] ), __( 'Option', 'lqdai' ), ++$key  );
                } 
            $out .= '</div>';

            wp_send_json( [
                'message' => $out,
            ] );
            
        } else {
            wp_send_json( [
                'error' => true,
                'message' => $response->get_error_message()
            ] );
        }
    
    }

    function insert_image( $post_id, $image_url ) {

        // Get the path to the uploads directory
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        // $filename = basename($image_url);
        $filename = sanitize_file_name(parse_url($image_url)['path']) . '.jpg';
    
        // Save the image to the uploads directory
        if ( wp_mkdir_p($upload_dir['path']) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
    
        file_put_contents($file, $image_data);
    
        // Get the attachment ID for the image
        $wp_filetype = wp_check_filetype($filename, null );
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name(str_replace('.jpg','', $filename)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attachment_id = wp_insert_attachment( $attachment, $file, $post_id );
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file );
        wp_update_attachment_metadata( $attachment_id, $attachment_data );
    
        // Set the attachment ID as the featured image for the post
        set_post_thumbnail($post_id, $attachment_id);
    
    }

    function lqdai_add_log() {

        $log = get_option( 'lqdai_logs' );

        if ( isset( $_POST['log'] ) ) {
            $log_message = sanitize_text_field( $_POST['log'] );
            $log .= $log_message . '<br>';
            update_option( 'lqdai_logs', $log );
            wp_send_json( [
                'message' => $log_message
            ] );
        }

    }

}
Liquid_ChatGPT::instance();

register_activation_hook( __FILE__, 'lqdai_flush' );
register_deactivation_hook( __FILE__, 'lqdai_flush' );

function lqdai_flush() { 
    flush_rewrite_rules(); 
}
