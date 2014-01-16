<?php
      
      if ( isset( $_POST['submit'] ) ) {

         $options = get_option( 'hmbkp_dropbox_settings' );

         $options['enabled'] = $_POST['dropbox-enabled'];

         update_option( 'hmbkp_dropbox_settings', $options );
      }
      
      $options = get_option( 'hmbkp_dropbox_settings' );

      $auth_url = admin_url( 'admin-ajax.php?action=hmbkp_dropbox_start_auth' );

      $unlink_nonce = wp_create_nonce( '_hmbkp_unlink_dropbox' );

      $unlink_url = admin_url( 'admin-ajax.php?action=hmbkp_unlink_dropbox&nonce='. $unlink_nonce );

?>

<?php if ( isset( $_POST['submit'] ) ): ?>
   <div class="updated message fade"><p><?php _e( 'Settings Saved.', 'hmbkp' ) ?></p></div>
<?php endif; ?>
<form method="post" action="<?php admin_url( '?page=backupwordpress-settings&tab=dropbox' ) ?>">

	<table class="form-table">

		 <tr>
            <th><?php _e( 'Enabled', 'hmbkp' ) ?></th>
            <td>
            	<input <?php checked( $options['enabled'], 'yes' ) ?> type="radio" name="dropbox-enabled" value="yes" /> Yes 
            	<input <?php checked( $options['enabled'], 'no' ) ?> type="radio" name="dropbox-enabled" value="no" /> No
            </td>
         </tr>

         <tr>

         <tr>
         	<th><?php _e( 'Connect', 'hmbkp' ) ?></th>
         	<td>
               <?php if ( function_exists( 'curl_version' ) ): ?>
               <?php if ( $options['access_token'] ):  ?>
                  <a class="button action" href="<?php echo $unlink_url ?>"><?php _e( 'Unlink Dropbox', 'hmbkp' ) ?></a>
               <?php else: ?>
                  <a class="button action" href="<?php echo $auth_url ?>"><?php _e( 'Connect to Dropbox', 'hmbkp' ) ?></a>
               <?php endif; ?>

         		<p class="description"><?php _e( 'Will only work if WP Backup plugin is connected to your Dropbox account.', 'hmbkp' ) ?></p>
               <?php else: ?>
               <?php _e( 'PHP extension curl is not installed or not enabled. Please enable it', 'hmbkp' ) ?>
               <?php endif; ?>
         	</td>
         </tr>

         <tr>
            <th><?php _e( 'Account Info', 'hmbkp' ) ?></th>
            <td>
               <?php do_action( 'hmbkp_dropbox_account_info' ) ?>
            </td>
         </tr>

	</table>
   <?php submit_button( 'Save Settings' )  ?>
</form>