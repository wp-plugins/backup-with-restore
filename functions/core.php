<?php

/**
 * Setup the plugin defaults on activation
 */
function hmbkp_activate() {

	// loads the translation files
	load_plugin_textdomain( 'hmbkp', false, HMBKP_PLUGIN_LANG_DIR );

	// Run deactivate on activation in-case it was deactivated manually
	hmbkp_deactivate();

}

/**
 * Cleanup on plugin deactivation
 *
 * Removes options and clears all cron schedules
 */
function hmbkp_deactivate() {

	// Clean up the backups directory
	hmbkp_cleanup();

	// Remove the plugin data cache
	delete_transient( 'hmbkp_plugin_data' );

	$schedules = HMBKP_Schedules::get_instance();

	// Clear schedule crons
	foreach ( $schedules->get_schedules() as $schedule )
		$schedule->unschedule();

}

/**
 * Handles anything that needs to be
 * done when the plugin is updated
 */
function hmbkp_update() {

	// Every update
	if ( get_option( 'hmbkp_plugin_version' ) && version_compare( HMBKP_VERSION, get_option( 'hmbkp_plugin_version' ), '>' ) ) {

		hmbkp_deactivate();

		// re-calcuate the backups directory and move to it.
		if ( ! defined( 'HMBKP_PATH' ) ) {

			$old_path = hmbkp_path();

			delete_option( 'hmbkp_path' );
			delete_option( 'hmbkp_default_path' );

			hmbkp_path_move( $old_path, hmbkp_path() );

		}

		// Force .htaccess to be re-written
		if ( file_exists( hmbkp_path() . '/.htaccess' ) )
			unlink( hmbkp_path() . '/.htaccess' );

		// Force index.html to be re-written
		if ( file_exists( hmbkp_path() . '/index.html' ) )
			unlink( hmbkp_path() . '/index.html' );

	}

	// Update the stored version
	if ( get_option( 'hmbkp_plugin_version' ) !== HMBKP_VERSION )
		update_option( 'hmbkp_plugin_version', HMBKP_VERSION );

}

/**
 * Setup the default backup schedules
 */
function hmbkp_setup_default_schedules() {

	$schedules = HMBKP_Schedules::get_instance();

	if ( $schedules->get_schedules() )
		return;

	/**
	 * Schedule a database backup daily and store backups
	 * for the last 2 weeks
	 */
	$database_daily = new HMBKP_Scheduled_Backup( 'default-1' );
	$database_daily->set_type( 'database' );
	$database_daily->set_reoccurrence( 'hmbkp_daily' );
	$database_daily->set_max_backups( 14 );
	$database_daily->save();

	/**
	 * Schedule a complete backup to run weekly and store backups for
	 * the last 3 months
	 */
	$complete_weekly = new HMBKP_Scheduled_Backup( 'default-2' );
	$complete_weekly->set_type( 'complete' );
	$complete_weekly->set_reoccurrence( 'hmbkp_weekly' );
	$complete_weekly->set_max_backups( 12 );
	$complete_weekly->save();

	function hmbkp_default_schedules_setup_warning() {
		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'WP Backup has setup your default schedules.', 'hmbkp' ) . '</strong> ' . __( 'By default WP Backup performs a daily backup of your database and a weekly backup of your database &amp; files. You can modify these schedules.', 'hmbkp' ) . '</p></div>';
	}
	add_action( 'admin_notices', 'hmbkp_default_schedules_setup_warning' );

}
add_action( 'admin_init', 'hmbkp_setup_default_schedules' );

/**
 * Return an array of cron schedules
 *
 * @param $schedules
 * @return array $reccurrences
 */
function hmbkp_cron_schedules( $schedules ) {

	$schedules['hmbkp_hourly']      = array( 'interval' => HOUR_IN_SECONDS, 'display'      => __( 'Once Hourly', 'hmbkp' ) );
	$schedules['hmbkp_twicedaily'] 	= array( 'interval' => 12 * HOUR_IN_SECONDS, 'display' => __( 'Twice Daily', 'hmbkp' ) );
	$schedules['hmbkp_daily']      	= array( 'interval' => DAY_IN_SECONDS, 'display'       => __( 'Once Daily', 'hmbkp' ) );
	$schedules['hmbkp_weekly'] 		= array( 'interval' => WEEK_IN_SECONDS, 'display'      => __( 'Once Weekly', 'hmbkp' ) );
	$schedules['hmbkp_fortnightly']	= array( 'interval' => 2 * WEEK_IN_SECONDS , 'display' => __( 'Once Fortnightly', 'hmbkp' ) );
	$schedules['hmbkp_monthly']		= array( 'interval' => 30 * DAY_IN_SECONDS, 'display'  => __( 'Once Monthly', 'hmbkp' ) );

	return $schedules;
}
add_filter( 'cron_schedules', 'hmbkp_cron_schedules' );

/**
 * Recursively delete a directory including
 * all the files and sub-directories.
 *
 * @param string $dir
 * @return bool
 * @throws Exception
 */
function hmbkp_rmdirtree( $dir ) {

	if ( strpos( HM_Backup::get_home_path(), $dir ) !== false )
		throw new Exception( 'You can only delete directories inside your WordPress installation' );

	if ( is_file( $dir ) )
		@unlink( $dir );

    if ( ! is_dir( $dir ) )
    	return false;

    $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ), RecursiveIteratorIterator::CHILD_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );

	foreach ( $files as $file ) {

		if ( $file->isDir() )
			@rmdir( $file->getPathname() );

		else
			@unlink( $file->getPathname() );

	}

	@rmdir( $dir );

}

/**
 * Get the path to the backups directory
 *
 * Will try to create it if it doesn't exist
 * and will fallback to default if a custom dir
 * isn't writable.
 */
function hmbkp_path() {

	global $is_apache;

	$path = untrailingslashit( get_option( 'hmbkp_path' ) );

	// Allow the backups path to be defined
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
		$path = untrailingslashit( HMBKP_PATH );

	// If the dir doesn't exist or isn't writable then use the default path instead instead
	if ( ( ! $path || ( is_dir( $path ) && ! is_writable( $path ) ) || ( ! is_dir( $path ) && ! is_writable( dirname( $path ) ) ) ) && $path !== hmbkp_path_default() )
		$path = hmbkp_path_default();

	// Create the backups directory if it doesn't exist
	if ( ! is_dir( $path ) && is_writable( dirname( $path ) ) )
		mkdir( $path, 0755 );

	// If the path has changed then cache it
	if ( get_option( 'hmbkp_path' ) !== $path )
		update_option( 'hmbkp_path', $path );

	// Protect against directory browsing by including a index.html file
	$index = $path . '/index.html';

	if ( ! file_exists( $index ) && is_writable( $path ) )
		file_put_contents( $index, '' );

	$htaccess = $path . '/.htaccess';

	// Protect the directory with a .htaccess file on Apache servers
	if ( $is_apache && function_exists( 'insert_with_markers' ) && ! file_exists( $htaccess ) && is_writable( $path ) ) {

		$contents[]	= '# ' . sprintf( __( 'This %s file ensures that other people cannot download your backup files.', 'hmbkp' ), '.htaccess' );
		$contents[] = '';
		$contents[] = '<IfModule mod_rewrite.c>';
		$contents[] = 'RewriteEngine On';
		$contents[] = 'RewriteCond %{QUERY_STRING} !key=' . HMBKP_SECURE_KEY;
		$contents[] = 'RewriteRule (.*) - [F]';
		$contents[] = '</IfModule>';
		$contents[] = '';

		insert_with_markers( $htaccess, 'WP Backup', $contents );

	}

    return HM_Backup::conform_dir( $path );

}

/**
 * Return the default backup path
 *
 * @return string path
 */
function hmbkp_path_default() {

	$path = untrailingslashit( get_option( 'hmbkp_default_path' ) );

	if ( empty( $path ) ) {

		$path = HM_Backup::conform_dir( trailingslashit( WP_CONTENT_DIR ) . 'backupwordpress-' . substr( HMBKP_SECURE_KEY, 0, 10 ) . '-backups' );

		update_option( 'hmbkp_default_path', $path );

	}

	$upload_dir = wp_upload_dir();

	// If the backups dir can't be created in WP_CONTENT_DIR then fallback to uploads
	if ( ( ( ! is_dir( $path ) && ! is_writable( dirname( $path ) ) ) || ( is_dir( $path ) && ! is_writable( $path ) ) ) && strpos( $path, $upload_dir['basedir'] ) === false ) {

		hmbkp_path_move( $path, $path = HM_Backup::conform_dir( trailingslashit( $upload_dir['basedir'] ) . 'backupwordpress-' . substr( HMBKP_SECURE_KEY, 0, 10 ) . '-backups' ) );

		update_option( 'hmbkp_default_path', $path );

	}

	return $path;

}

/**
 * Move the backup directory and all existing backup files to a new
 * location
 *
 * @param string $from path to move the backups dir from
 * @param string $to path to move the backups dir to
 * @return void
 */
function hmbkp_path_move( $from, $to ) {

	if ( ! trim( untrailingslashit( trim( $from ) ) ) || ! trim( untrailingslashit( trim( $to ) ) ) )
		return;

	// Create the new directory if it doesn't exist
	if ( is_writable( dirname( $to ) ) && ! is_dir( $to ) )
	    mkdir( $to, 0755 );

	// Bail if we couldn't
	if ( ! is_dir( $to ) || ! is_writable( $to ) )
	    return false;

	update_option( 'hmbkp_path', $to );

	// Bail if the old directory doesn't exist
	if ( ! is_dir( $from ) )
		return false;

	// Cleanup before we start moving things
	hmbkp_cleanup();

	// Move any existing backups
	if ( $handle = opendir( $from ) ) {

	    while ( false !== ( $file = readdir( $handle ) ) )
	    	if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'zip' )
	    		if ( ! @rename( trailingslashit( $from ) . $file, trailingslashit( $to ) . $file ) )
	    			copy( trailingslashit( $from ) . $file, trailingslashit( $to ) . $file );

	    closedir( $handle );

	}

	// Only delete the old directory if it's inside WP_CONTENT_DIR
	if ( strpos( $from, WP_CONTENT_DIR ) !== false )
		hmbkp_rmdirtree( $from );

}

/**
 * Check if a backup is possible with regards to file
 * permissions etc.
 *
 * @return bool
 */
function hmbkp_possible() {

	if ( ! is_writable( hmbkp_path() ) || ! is_dir( hmbkp_path() ) )
		return false;

	return true;
}

/**
 * Remove any non backup.zip files from the backups dir.
 *
 * @return void
 */
function hmbkp_cleanup() {

	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
		return;

	$hmbkp_path = hmbkp_path();

	if ( ! is_dir( $hmbkp_path ) )
		return;

	if ( $handle = opendir( $hmbkp_path ) ) {

    	while ( false !== ( $file = readdir( $handle ) ) )
    		if ( ! in_array( $file, array( '.', '..', 'index.html' ) ) && pathinfo( $file, PATHINFO_EXTENSION ) !== 'zip' )
				hmbkp_rmdirtree( trailingslashit( $hmbkp_path ) . $file );

    	closedir( $handle );

    }

}

/**
 * Handles changes in the defined Constants
 * that users can define to control advanced
 * settings
 */
function hmbkp_constant_changes() {

	// If a custom backup path has been set or changed
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && HM_Backup::conform_dir( HMBKP_PATH ) !== ( $from = HM_Backup::conform_dir( get_option( 'hmbkp_path' ) ) ) )
	  hmbkp_path_move( $from, HMBKP_PATH );

	// If a custom backup path has been removed
	if ( ( ( defined( 'HMBKP_PATH' ) && ! HMBKP_PATH ) || ! defined( 'HMBKP_PATH' ) && hmbkp_path_default() !== ( $from = HM_Backup::conform_dir( get_option( 'hmbkp_path' ) ) ) ) )
	  hmbkp_path_move( $from, hmbkp_path_default() );

	// If the custom path has changed and the new directory isn't writable
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && ! is_writable( HMBKP_PATH ) && get_option( 'hmbkp_path' ) === HMBKP_PATH && is_dir( HMBKP_PATH ) )
		hmbkp_path_move( HMBKP_PATH, hmbkp_path_default() );

}

/**
 * Get the max email attachment filesize
 *
 * Can be overridden by defining HMBKP_ATTACHMENT_MAX_FILESIZE
 *
 * return int the filesize
 */
function hmbkp_get_max_attachment_size() {

	$max_size = '10mb';

	if ( defined( 'HMBKP_ATTACHMENT_MAX_FILESIZE' ) && wp_convert_hr_to_bytes( HMBKP_ATTACHMENT_MAX_FILESIZE ) )
		$max_size = HMBKP_ATTACHMENT_MAX_FILESIZE;

	return wp_convert_hr_to_bytes( $max_size );

}
