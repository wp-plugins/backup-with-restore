<?php

/**
 * Add the backups menu item
 * to the admin menu
 *
 * @return null
 */
function hmbkp_admin_menu() {
	$page = add_menu_page( __( 'Manage Backups','hmbkp' ), __( 'Backups','hmbkp' ), ( defined( 'HMBKP_CAPABILITY' ) && HMBKP_CAPABILITY ) ? HMBKP_CAPABILITY : 'manage_options', HMBKP_PLUGIN_SLUG, 'hmbkp_manage_backups' );

	$sub_page = add_submenu_page( HMBKP_PLUGIN_SLUG, __( 'Settings','hmbkp' ), __( 'Settings','hmbkp' ), 'manage_options', 'backupwordpress-settings', 'hmbkp_settings_page' );
	
	add_filter( "load-$page", 'hmbkp_contextual_help' );
}
add_action( 'admin_menu', 'hmbkp_admin_menu' );

/**
 * Load the backups admin page
 * when the menu option is clicked
 *
 * @return null
 */
function hmbkp_manage_backups() {
	require_once( HMBKP_PLUGIN_PATH . '/admin/page.php' );
}

/**
 * Load the backups admin settings page
 * when the menu option is clicked
 *
 * @return null
 */
function hmbkp_settings_page() {
	require_once( HMBKP_PLUGIN_PATH . '/admin/settings-page.php' );
}

/**
 * Add a link to the backups page to the plugin action links.
 *
 * @param array $links
 * @param string $file
 * @return array $links
 */
function hmbkp_plugin_action_link( $links, $file ) {

	if ( strpos( $file, HMBKP_PLUGIN_SLUG ) !== false )
		array_push( $links, '<a href="admin.php?page=' . esc_attr( HMBKP_PLUGIN_SLUG ) . '">' . __( 'Backups', 'hmbkp' ) . '</a>' );

	return $links;

}
add_filter( 'plugin_action_links', 'hmbkp_plugin_action_link', 10, 2 );

/**
 * Add Contextual Help to Backups admin page.
 *
 * Help is pulled from the readme FAQ.
 *
 * @return null
 */
function hmbkp_contextual_help() {

	// Pre WordPress 3.3 compat
	if ( ! method_exists( get_current_screen(), 'add_help_tab' ) )
		return;

	$warning = '';

	ob_start();
	require_once( HMBKP_PLUGIN_PATH . '/admin/constants.php' );
	$constants = ob_get_clean();

	ob_start();
	require_once( HMBKP_PLUGIN_PATH . '/admin/faq.php' );
	$faqs = ob_get_clean();

	
	get_current_screen()->add_help_tab( array( 'title' => __( 'FAQ', 'hmbkp' ), 'id' => 'hmbkp_faq', 'content' => wp_kses_post( $warning . $faqs ) ) );
	
	get_current_screen()->add_help_tab( array( 'title' => __( 'Constants', 'hmbkp' ), 'id' => 'hmbkp_constants', 'content' => wp_kses_post( $constants ) ) );

	get_current_screen()->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'hmbkp' ) . '</strong></p>'  . '<p><a href="" target="_blank">' . __( 'Support Forums', 'hmbkp' ) .'</a></p>' 
	);

}
