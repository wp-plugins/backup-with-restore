<?php

/**
 * Store backups to the Dropbox
 *
 * @version 1.0
 */
class Wpbp_Dropbox {

	private $key = "s3gajf8pv92hdex";

	private $secret = "wkrvntpbrapwrvp";

	function __construct() {


		if ( ! class_exists( 'B_Dropbox_Wrapper' ) )
			require_once( HMBKP_PLUGIN_PATH . 'lib/dropbox/dropbox.php' );

		$options = array(
				'enabled' => 'no',
				'access_token' => NULL
			);

		if ( ! get_option( 'hmbkp_dropbox_settings' ) )
			update_option( 'hmbkp_dropbox_settings', $options );

		add_action( 'wp_ajax_hmbkp_dropbox_start_auth', array( $this, 'start_auth') );

		add_action( 'wp_ajax_hmbkp_connect_dropbox', array( $this, 'finish_auth' ) );

		add_action( 'wp_ajax_hmbkp_unlink_dropbox', array( $this, 'unlink_account' ) );

		add_action( '_hmbkp_backup_complete', array( $this, 'upload') );

		add_action( 'hmbkp_dropbox_account_info', array( $this, 'show_account_info' ) );

	}

	function start_auth() {

		$dropbox = new B_Dropbox_Wrapper( $this->key, $this->secret );

		try {
			$token = $dropbox->oAuthRequestToken();	
		} catch (Exception $e) {
			wp_die( $e->getMessage() );
		}

		$nonce = wp_create_nonce( '_hmbkp_connect_dropbox' );

		$redirect_url = admin_url( 'admin-ajax.php?action=hmbkp_connect_dropbox&secret='.$token['oauth_token_secret'].'&nonce='. $nonce );
		
		$dropbox->oAuthAuthorize( $token['oauth_token'], $redirect_url );
		
	}

	function finish_auth() {

		if ( ! wp_verify_nonce( $_GET['nonce'], '_hmbkp_connect_dropbox' ) )
			wp_die( 'Invalid Nonce' );

		if ( isset( $_GET['not_approved'] ) )
			wp_safe_redirect( admin_url( 'admin.php?page=backupwordpress-settings&tab=dropbox' ) );

		$options = get_option( 'hmbkp_dropbox_settings' );

		$dropbox = new B_Dropbox_Wrapper( $this->key, $this->secret );

		$dropbox->setOAuthToken( $_GET['oauth_token'] );
		$dropbox->setOAuthTokenSecret( $_GET['secret'] );

		try {

			$token = $dropbox->oAuthAccessToken( $_GET['oauth_token'] );
		
		} catch (Exception $e) {
			
			wp_die( $e->getMessage() );

		}

		
		$options['access_token']['token'] = $token['oauth_token'];
		$options['access_token']['secret'] = $token['oauth_token_secret'];
		$options['access_token']['uid'] = $token['uid'];

		update_option( 'hmbkp_dropbox_settings', $options );

		wp_safe_redirect( admin_url( 'admin.php?page=backupwordpress-settings&tab=dropbox' ) );

		exit;

	}

	function unlink_account() {

		if ( ! wp_verify_nonce( $_GET['nonce'], '_hmbkp_unlink_dropbox' ) )
			wp_die( 'Invalid Nonce' );

		$options = get_option( 'hmbkp_dropbox_settings' );

		$options['access_token'] = NULL;

		update_option( 'hmbkp_dropbox_settings', $options );

		wp_safe_redirect( admin_url( 'admin.php?page=backupwordpress-settings&tab=dropbox' ) );

		exit;

	}

	public function upload( $filepath ) {

		$options = get_option( 'hmbkp_dropbox_settings' );

		if ( $options['enabled'] !== 'yes' )
			return;

		$dropbox = new B_Dropbox_Wrapper( $this->key, $this->secret );

		$dropbox->setOAuthToken( $options['access_token']['token'] );

		$dropbox->setOAuthTokenSecret( $options['access_token']['secret'] );

		$upload_path = date("20y/F/").basename($filepath);

		$response = $dropbox->chunked_upload( $filepath, $upload_path, true, null, null );

	}

	function download( $id ) {


	}

	function show_account_info() {

		$options = get_option( 'hmbkp_dropbox_settings' );

		if ( ! $options['access_token'] )
			return;

		$dropbox = new B_Dropbox_Wrapper( $this->key, $this->secret );

		$dropbox->setOAuthToken( $options['access_token']['token'] );

		$dropbox->setOAuthTokenSecret( $options['access_token']['secret'] );

		if ( ! get_transient( 'hmbkp_dropbox_account_info'  ) ) {
			$response = $dropbox->accountInfo();

			set_transient( 'hmbkp_dropbox_account_info', $response, 60 * 60 * 5 );
		} else {

			$response = get_transient( 'hmbkp_dropbox_account_info'  );

		}

		printf( '<code>Name: <strong>%s</strong> & Email: <strong>%s</strong></code>', $response['display_name'], $response['email'] );

	}

}