<?php 
/**
 * Custom option and settings
 */
function lqdai_settings_init() {
	// Register a new setting for "wporg" page.
	register_setting( 'lqdai', 'lqdai_options' );

	// Register a new section in the "wporg" page.
	add_settings_section(
		'lqdai_section_settings',
		__( 'Options', 'lqdai' ), 'lqdai_section_settings_callback',
		'lqdai'
	);

	// Register fields in the "lqdai_section_settings" section, inside the "lqdai" page.
    add_settings_field(
        'api_key',
        __( 'API Key', 'lqdai' ), 
        'api_key_callback',
        'lqdai', // Page
        'lqdai_section_settings' // Section           
    ); 

    add_settings_field(
        'model',
        __( 'Model', 'lqdai' ), 
        'model_callback',
        'lqdai', // Page
        'lqdai_section_settings' // Section           
    ); 

    add_settings_field(
        'max_tokens',
        __( 'Max Tokens', 'lqdai' ), 
        'max_tokens_callback',
        'lqdai', // Page
        'lqdai_section_settings' // Section           
    );

	add_settings_field(
        'api_key_unsplash',
        __( 'Unsplash API Key', 'lqdai' ), 
        'api_key_unsplash_callback',
        'lqdai', // Page
        'lqdai_section_settings' // Section           
    ); 

}

/**
 * Register our lqdai_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'lqdai_settings_init' );

/**
 * Custom option and settings:
 *  - callback functions
 */

function lqdai_section_settings_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Set API options', 'lqdai' ); ?></p>
	<?php
}

function api_key_callback() {
    $options = get_option( 'lqdai_options' );
    printf(
        '<input type="text" id="api_key" name="lqdai_options[api_key]" value="%s" /><p class="description">%s <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a></p>',
        isset( $options[ 'api_key' ] ) ? esc_attr( $options[ 'api_key' ] ) : '',
        __( 'You can find your API key at', 'lqdai' )
    );
}

function api_key_unsplash_callback() {
    $options = get_option( 'lqdai_options' );
    printf(
        '<input type="text" id="api_key_unsplash" name="lqdai_options[api_key_unsplash]" value="%s" /><p class="description">%s <a href="https://unsplash.com/oauth/applications" target="_blank">https://unsplash.com/oauth/applications</a></p>',
        isset( $options[ 'api_key_unsplash' ] ) ? esc_attr( $options[ 'api_key_unsplash' ] ) : '',
        __( 'You can find your API key at', 'lqdai' )
    );
}

function model_callback() {
    $options = get_option( 'lqdai_options' );

	?>
	<select id="model" name="lqdai_options[model]">
		<option value="text-davinci-003" <?php echo isset( $options[ 'model' ] ) ? ( selected( $options[ 'model' ], 'text-davinci-003', false ) ) : ( '' ); ?>>
			<?php esc_html_e( 'text-davinci-003', 'lqdai' ); ?>
		</option>
		<option value="text-curie-001" <?php echo isset( $options[ 'model' ] ) ? ( selected( $options[ 'model' ], 'text-curie-001', false ) ) : ( '' ); ?>>
			<?php esc_html_e( 'text-curie-001', 'lqdai' ); ?>
		</option>
		<option value="text-babbage-001" <?php echo isset( $options[ 'model' ] ) ? ( selected( $options[ 'model' ], 'text-babbage-001', false ) ) : ( '' ); ?>>
			<?php esc_html_e( 'text-babbage-001', 'lqdai' ); ?>
		</option>
		<option value="text-ada-001" <?php echo isset( $options[ 'model' ] ) ? ( selected( $options[ 'model' ], 'text-ada-001', false ) ) : ( '' ); ?>>
			<?php esc_html_e( 'text-ada-001', 'lqdai' ); ?>
		</option>
	</select>
	<p class="description">
		<?php esc_html_e( 'GPT-3 models can understand and generate natural language. We offer four main models with different levels of power suitable for different tasks. Davinci is the most capable model, and Ada is the fastest.', 'lqdai' ); ?>
        <a href="https://platform.openai.com/docs/models/gpt-3" target="_blank"><?php esc_html_e( 'More info', 'lqdai' ); ?><a>
	</p>
	<?php
}

function max_tokens_callback() {
    $options = get_option( 'lqdai_options' );
    printf(
        '<input type="number" id="max_tokens" name="lqdai_options[max_tokens]" value="%s" /><p class="description">%s</p>',
        isset( $options[ 'max_tokens' ] ) ? esc_attr( $options[ 'max_tokens' ] ) : '2048',
        esc_html( 'Limits the maximum number of tokens a language model can process at once in OpenAI', 'lqdai' )
    );
}

/**
 * Add the top level menu page.
 */
function lqdai_options_page() {
	add_menu_page(
		'Liquid ChatGPT',
		'Liquid ChatGPT',
		'manage_options',
		'lqdai',
		'lqdai_options_page_html',
        'dashicons-image-filter'
	);
}

/**
 * Register our lqdai_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'lqdai_options_page' );


/**
 * Top level menu callback function
 */
function lqdai_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages
	// check if the user have submitted the settings
	// WordPress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'lqdai_messages', 'lqdai_message', __( 'Settings Saved', 'lqdai' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'lqdai_messages' );
	?>
	<div class="wrap lqdai-options">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
        <?php
            // output security fields for the registered setting "wporg"
            settings_fields( 'lqdai' );
            // output setting sections and their fields
            // (sections are registered for "wporg", each field is registered to a specific section)
            do_settings_sections( 'lqdai' );
            // output save settings button
            submit_button( 'Save Settings' );
        ?>
        </form>
	</div>
	<?php
}