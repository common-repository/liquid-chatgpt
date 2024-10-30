<?php 

/**
 * Add the sub-menu page.
 */
function lqdai_logs_page() {
	add_submenu_page(
		'lqdai',
		__( 'Logs', 'lqdai' ),
		__( 'Logs', 'lqdai' ),
		'manage_options',
		'lqdai-logs',
		'lqdai_logs_page_html'
	);
}

/**
 * Register our lqdai_logs_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'lqdai_logs_page' );


/**
 * Top level menu callback function
 */
function lqdai_logs_page_html() {

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<div class="lqdai-logs"><?php echo wp_kses( get_option( 'lqdai_logs' ), array( 'br' => array() ) ); ?></div>
	</div>
	<?php
}