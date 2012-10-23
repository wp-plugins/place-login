<?php

/**
 * Admin Class
 *
 * @package plogin-login
 * @subpackage Administration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'pLogin_Admin' ) ) :
/*
 * pLogin_Admin Class
 */
class pLogin_Admin {

	public function __construct(){
		$this->setup_actions();
	}


	private function setup_actions(){
		add_action( 'admin_init', array( $this, 'register_admin_options' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
	
	}

	public function add_default_options(){
		$options = plogin_get_default_options();
		foreach ( $options as $key => $value )
			add_option( $key, $value );
	}
	
	public function register_admin_options(){
		$this->add_default_options();
	
		//Usage section
		add_settings_section('plogin_usage', __('Usage','plogin'), array( $this, 'plogin_usage_section' ), 'plogin');

		//Redirect section
		add_settings_section('plogin_redirect', __('Redirect','plogin'), array( $this, 'plogin_redirect_section' ), 'plogin');
		/**Fields**/
		add_settings_field('plogin_after_login', __('After login','plogin'), array( $this, 'plogin_redirect_afterlogin' ), 'plogin', 'plogin_redirect');
	 	register_setting  ( 'plogin', 'plogin_after_login' );
		add_settings_field('plogin_after_logout', __('After logout','cl'), array( $this, 'plogin_redirect_afterlogout' ), 'plogin', 'plogin_redirect');
	 	register_setting  ( 'plogin', 'plogin_after_logout' );

		//Login links
		add_settings_section('plogin_loginlinks', __('Login links','plogin'), array( $this, 'plogin_loginlinks_section' ), 'plogin');
		/**Fields**/
		add_settings_field('plogin_parent_page', __('Parent page','plogin'), array($this, 'plogin_loginlinks_parentpage' ), 'plogin', 'plogin_loginlinks');
	 	register_setting  ( 'plogin', 'plogin_parent_page' );			
		add_settings_field('plogin_register', __('Register','plogin'), array($this, 'plogin_loginlinks_register' ), 'plogin', 'plogin_loginlinks');
	 	register_setting  ( 'plogin', 'plogin_register' );
		add_settings_field('plogin_lostpwd', __('Lost password','plogin'), array( $this, 'plogin_loginlinks_lostpwd' ), 'plogin', 'plogin_loginlinks');
	 	register_setting  ( 'plogin', 'plogin_lostpwd' );

		//Logged in links
		add_settings_section('plogin_loggedinlinks', __('Logged in links','plogin'), array( $this, 'plogin_loggedinlinks_section' ), 'plogin');
		/**Fields**/
		add_settings_field('plogin_links', __('Links','plogin'), array( $this, 'plogin_loggedinlinks_links' ), 'plogin', 'plogin_loggedinlinks');
	 	register_setting  ( 'plogin', 'plogin_links' );	
	}

	public function plogin_usage_section(){
		echo '<p>' .__("To have the login link or button, you should use the following code in your template files: <br><code>&lt;?php if &#40; function_exists&#40; 'plogin_form' &#41; &#41; plogin_form&#40;&#41;; ?&gt;</code>.<br/>If you want to have a login form in your sidebar, you should use the Place Login widget</code>") .'</p>';
	}

	public function plogin_redirect_section(){
		echo '<p>' .__('Url to redirect after the user login or logout.') .'</p>';
	}

	public function plogin_redirect_afterlogin(){
?>
		<input name="plogin_after_login" type="text" id="plogin_after_login" value="<?php echo get_option('plogin_after_login'); ?>" class="regular-text" />
		<label for="plogin_after_login"><?php _e( 'Leave blank to use the current page', 'plogin' ); ?></label>
<?php
	}

	public function plogin_redirect_afterlogout(){
?>
		<input name="plogin_after_logout" type="text" id="plogin_after_logout" value="<?php echo get_option('plogin_after_logout'); ?>" class="regular-text" />
		<label for="plogin_after_logout"><?php _e( 'Leave blank to use the current page', 'plogin' ); ?></label>
<?php	
	}

	public function plogin_loginlinks_section(){
		echo '<p>' .__('Links to appear in the login form.') .'</p>';
	}

	public function plogin_loginlinks_parentpage(){
		echo wp_dropdown_pages( array( 'name' => 'plogin_parent_page', 'echo' => 0, 'exclude_tree' => get_option( 'page_on_front' ), 'selected' => get_option( 'plogin_parent_page' )  ) )
?>		
		<label for="plogin_parent_page"><?php _e( 'Create the default pages as its children', 'plogin' ); ?></label>
<?php		
	}
	
	public function plogin_loginlinks_register(){
?>
		<input name="plogin_register" type="text" id="plogin_register" value="<?php echo get_option('plogin_register'); ?>" class="regular-text" />
		<label for="plogin_register"><?php _e( 'Leave blank to use the default page', 'plogin' ); ?></label>
<?php		
	}

	public function plogin_loginlinks_lostpwd(){
?>
		<input name="plogin_lostpwd" type="text" id="plogin_lostpwd" value="<?php echo get_option('plogin_lostpwd'); ?>" class="regular-text" />
		<label for="plogin_lostpwd"><?php _e( 'Leave blank to use the default page', 'plogin' ); ?></label>
<?php		
	}	

	public function plogin_loggedinlinks_section(){
		echo '<p>' .__('Links to appear once the user has logged in.') .'</p>';	
	}

	public function plogin_loggedinlinks_links(){
?>	
		<textarea id="plogin_links" class="large-text" cols="50" rows="10" name="plogin_links"><?php echo get_option('plogin_links'); ?></textarea>
		<label for="plogin_links"><?php _e( 'Enter one link per line. Note: Logout link will always show regardless. Tip: Add <code>|true</code> after a link to only show it to admin users or alternatively use a <code>|user_capability</code> and the link will only be shown to users with that capability (see <a href=\'http://codex.wordpress.org/Roles_and_Capabilities\' target=\'_blank\'>Roles and Capabilities</a>).<br/> You can type <code>%USERNAME%</code> and <code>%%USERID%%</code> which will be replaced by the user\'s info. Also, You can type <code>%PROFILE%</code> to get the link for the user profile. Default: <br/>&lt;a href="%PROFILE%"&gt;Dashboard&lt;/a&gt;', 'plogin' ); ?></label>
<?php			
	}

	public function register_admin_page() {

		// Place Login settings
		add_options_page   ( __( 'Place Login',  'plogin' ), __( 'Place Login',  'plogin' ), 'manage_options', 'plogin', array($this, 'plogin_admin_settings' ) );
	}

	public function plogin_admin_settings(){
		global $wp_rewrite;
?>

	<div class="wrap">

		<?php screen_icon(); ?>

		<h2><?php _e( 'Place Login Options', 'plogin' ) ?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( 'plogin' ); ?>

			<?php do_settings_sections( 'plogin' ); ?>

			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php _e( 'Save Changes', 'plogin' ); ?>" />
			</p>
		</form>
	</div>

<?php	
		$wp_rewrite->flush_rules();
	}
	
}
endif; // class_exists check

function plogin_admin() {
	global $plogin;

	$plogin->admin = new pLogin_Admin();
	//new pLogin_Admin();

}
?>