<?php

/**
 * Login form widgetclass.
 *
 * @package place-login
 * @subpackage Widget
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/*
 * PlaceLogin_Widget Class
 */
class plogin_Widget extends WP_Widget {

	function register_widget() {
		global $plogin; //, $wp_scripts;
		
		wp_enqueue_script('jquery');
		// load jQuery UI dialog
		wp_enqueue_script('jquery-ui-dialog');
		// get registered script object for jquery ui
		//$ui = $wp_scripts->query('jquery-ui-core');
		
		wp_enqueue_script( 'plogin-ajax', $plogin->script_dir . '/plogin-ajax.js', array( 'jquery','jquery-ui-dialog' ), '1.0' );
		wp_enqueue_script( 'plogin-menu', $plogin->script_dir . '/plogin-menu.js', array( 'jquery' ), '1.0' );
		//wp_enqueue_style( 'jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/redmond/jquery-ui.css', false, $ui->ver );
		//wp_enqueue_style( 'plogin-form', $plogin->css_dir . '/plogin-form.css', array('jquery-ui'), '1.0' );
		
		// Pass variables to script
		$plogin_params = array(
			'ajax_url' 				=> ( is_ssl() || force_ssl_admin() || force_ssl_login() ) ? str_replace('http:', 'https:', admin_url('admin-ajax.php')) : str_replace('https:', 'http:', admin_url('admin-ajax.php')),
			'login_nonce' 			=> wp_create_nonce("plogin-action")
		);
		wp_localize_script( 'plogin-ajax', 'plogin_params', $plogin_params );
		
		register_widget( 'plogin_Widget' );
	}
	
	function plogin_Widget() {
		$widget_ops = apply_filters( 'plogin_widget_options', array(
			'classname'   => 'plogin_widget',
			'description' => __( 'The login widget.', 'plogin' )
		) );

		parent::WP_Widget( false, __( 'Place Login Widget', 'plogin' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		global $plogin, $current_user;
		extract( $args );

		/* Variable from the widget settings */
		$title 			= apply_filters( 'plogin_widget_title', $instance['title'] );		
		
		echo $before_widget;

		if ( !empty( $title ) )
			echo $before_title . $title . $after_title;
	
		get_currentuserinfo();
		
		if ($current_user->ID != ''){
			// User is logged in
			plogin_user_options($current_user->ID, $current_user->display_name);
		}
		else{
			// User is NOT logged in!		
			//require( $plogin->template_dir . '/plogin_login_form.php'       );
			plogin_login_form();
		}
		
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ){
		$instance['title']= strip_tags( $new_instance['title'] );

		return $instance;
	}

	function form( $instance ){
		// Form values
		$title    = !empty( $instance['title'] )    ? esc_attr( $instance['title'] )    : '';
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'plogin' ); ?>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label>
		</p>		
<?php		
		echo '<p>' .sprintf(__('To set up options, please go to the <a href="%s">settings</a>','ccp'),'options-general.php?page=plogin') .'</p>';
	}
}
?>