<div class="wrap">

	<?php screen_icon( HMBKP_PLUGIN_SLUG ); ?>

	<h2><?php _e( 'Manage Settings', 'hmbkp' ); ?></h2>

	<?php

		$tab = 'dropbox';

		if ( isset( $_GET['tab'] ) ) 
			$tab = $_GET['tab'];
		
		hmbkp_settings_admin_tabs( $tab );

		require_once( HMBKP_PLUGIN_PATH . 'admin/settings/'.$tab.'.php' );

	?> 


	<p class="howto"><?php printf( __( 'If you need help getting things working then check the FAQ by clicking on help in the top right hand corner of this page.', 'hmbkp' ), '<a href="mailto:support@hmn.md">support@hmn.md</a>' ); ?></p>

</div>

<?php

function hmbkp_settings_admin_tabs( $current = 'dropbox' ) {
    $tabs = array( 'dropbox' => __( 'Dropbox', 'hmbkp' ) );
    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=backupwordpress-settings&tab=$tab'>$name</a>";

    }
    echo '</h2>';
}

?>