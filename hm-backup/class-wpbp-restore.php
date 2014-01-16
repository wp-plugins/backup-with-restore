<?php

/**
 * Restore Database and file class
 *
 * @version 1.0
 */
class Wpbp_Restore {
	
	/**
	 * The path where the backup file is stored
	 *
	 * @string
	 * @access private
	 */
	private $path = '';

	/**
	 * The backup type, must be either complete, file or database
	 *
	 * @string
	 * @access private
	 */
	private $type = '';

	function __construct() {

	}

	public function start( $id, $path ) {

		$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $id ) ) );

		$this->type = $schedule->get_type();

		$this->path = sanitize_text_field( base64_decode( $path ) );

		$this->restore();

	}

	function restore() {

		if ( ! class_exists( 'PclZip' ) )
			require ABSPATH . 'wp-admin/includes/class-pclzip.php';

		if ( $this->type == 'complete' )
			$this->restore_complete();

		if ( $this->type == 'database' )
			$this->restore_database();

		if ( $this->type == 'file' )
			$this->restore_files();

	}

	function restore_complete() {

		$filename = basename( $this->path, '.zip' ) . '.sql';

		$file_path = ABSPATH . $filename;

		$this->restore_files();

		$this->restore_database( $file_path );

	}

	function restore_database( $file = null ) {

		global $wpdb;

		if ( ! $file ) {

			$archive = new PclZip( $this->path );

			$filename = basename( $this->path, '.zip' ) . '.sql';

			$dir = hmbkp_path_default();

			if ( defined( 'HMBKP_PATH' ) )
				$dir = HMBKP_PATH;

			$file_path = $dir . DIRECTORY_SEPARATOR . $filename;

			if ( ! $archive->extract( PCLZIP_OPT_PATH, $dir ) )
				wp_die( 'Unable to extract zip file. Please check that zlib php extension is enabled. <button onclick="history.go(-1);">Go Back</button>', 'ZIP Error' );

		} else {

			$file_path = $file;

		}

		$templine = '';

		$lines = file( $file_path );

		foreach ( $lines as $line ):

		if (substr($line, 0, 2) == '--' || $line == '')
        	continue;

        $templine .= $line;

        if ( substr( trim( $line ), -1, 1 ) == ';' ) {

        	if ( $wpdb->query( $templine ) === false )
        		wp_die( $wpdb->last_error . ' <button onclick="history.go(-1);">Go Back</button>' , 'Database Error' );

        	$templine = '';

        }

		endforeach;

		@unlink( $file_path );

	}

	function restore_files( $file = null ) {

		if ( ! $file )
			$archive = new PclZip( $this->path );
		else
			$archive = new PclZip( $file );

		if ( ! $archive->extract( PCLZIP_OPT_PATH, ABSPATH ) )
			wp_die( 'Unable to extract zip file. Please check that zlib php extension is enabled. <button onclick="history.go(-1);">Go Back</button>', 'ZIP Error' );
		
	}

}