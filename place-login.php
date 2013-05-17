<?php
/*
Plugin Name: Place Login
Plugin URI: http://boroniatechnologies.com/place-login
Description: This plugin can add a login button or widget in any region of your web page.
Version: 1.0.1
Author: Catherine Lebastard
Author URI: http://www.boroniatechnologies.com
License: GPLv2 or later
*/
/*
    Copyright (c) 2012 - 2013 Catherine Lebastard (email: clebastard@boroniatechnologies.com)

    This program is free software; you can redistribute it and/or modify it under 
    the terms of the GNU General Public License as published by the Free Software 
    Foundation; either version 3 of the License, or (at your option) any later 
    version.

    This program is distributed in the hope that it will be useful, but WITHOUT 
    ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
    FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along with 
    this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'pLogin' ) ) :
/*
 * pLogin Class
 */
class pLogin {
	/** Version ************************************************************************/
	public $version = '1.0';

	// Paths
	/** Basename of the Place Login plugin ************************************/
	public $basename = '';

	/** Absolute path to the Place Login Plugin *******************************/
	public $plugin_dir = '';

	/** URL to the Place Login directory **************************************/
	public $plugin_url = '';
	
	/** Path to the Place Login language directory ****************************/
	public $lang_dir = '';
	
	/** Path to the Place Login script directory ******************************/
	public $script_dir = '';

	/** Path to the Place Login css directory *********************************/
	public $css_dir = '';
	
	/** Path for the login template ****************************************************/
	public $template_themedir = '';
	public $template_dir = '';

	/** Virtual Page *******************************************************************/
	public $virtual_pages = array();
	
	public function __construct(){
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();		
	}
	
	private function setup_globals(){

		// Paths
		$this->file          = __FILE__;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_dir    = plugin_dir_path( $this->file );
		$this->plugin_url    = plugin_dir_url( $this->file );
		$this->lang_dir      = dirname( $this->basename) .'languages';
		$this->script_dir    = $this->plugin_url .'js';
		$this->css_dir    = $this->plugin_url .'css';
		$this->template_themedir = get_stylesheet_directory() .'/templates'; 
		$this->template_dir  = $this->plugin_dir .'templates';
		$this->virtual_pages = array(
			'register' => array( 'title' => 'Registering new members', 'template' => 'plogin_register.php'),
			'lostpwd' => array( 'title' => 'Lost Password', 'template' => 'plogin_lostpwd.php' ),
			'profile' => array( 'title' => 'Edit Profile', 'template' => 'plogin_profile.php' )
		);
		
	}

	private function includes(){
		require( $this->plugin_dir . 'includes/widget.php'       ); // Login widget
		require( $this->plugin_dir . 'includes/common-functions.php'       ); // Common functions
		
		/** Admin *************************************************************/

		// Quick admin check and load if needed
		if ( is_admin() ) require( $this->plugin_dir . 'includes/admin.php' ); // Admin options
	}

	private function setup_actions(){		
		//admin
		if ( is_admin() ) plogin_admin();
	
		//Virtual pages
		add_filter('rewrite_rules_array', array($this, 'vpage_insertrules'));
		add_filter('query_vars', array($this, 'vpage_queryvars'));
		remove_filter('wp_head', 'rel_canonical');
		add_filter('wp_head',array($this,'plogin_rel_canonical'));
		add_action('template_include',array($this,'vpage_template'));
	
		//Widget
		add_action( 'wp_enqueue_scripts', array($this, 'login_style') );
		add_action( 'widgets_init', array( 'plogin_Widget', 'register_widget') );
		/**
		* Process ajax login
		*/
		add_action( 'wp_ajax_plogin_process', array($this, 'plogin_ajax_process') );
		add_action( 'wp_ajax_nopriv_plogin_process', array($this, 'plogin_ajax_process') );

	}
	
	public function login_style(){
		global $plogin, $wp_scripts;
		
		// get registered script object for jquery ui
		$ui = $wp_scripts->query('jquery-ui-core');

		wp_enqueue_style( 'jquery-ui-theme', $plogin->css_dir .'/jquery-ui-redmond.css', false, $ui->ver );
		wp_enqueue_style( 'plogin-form', $plogin->css_dir . '/plogin-form.css', array('jquery-ui-theme'), '1.0' );
	}
	
	public function vpage_insertrules($rules){
		$newrules = array();
		$ppage = plogin_get_page_slug(get_option('plogin_parent_page'));

		foreach($this->virtual_pages as $slug => $content){
			$newrules['(' .$ppage .')/' . $slug . '/?$'] = 'index.php?pagename=$matches[1]&vpage=' . $slug;
		}	
		
		return $newrules + $rules;
	}
	
	public function vpage_queryvars($vars){
		array_push($vars, 'vpage');
		return $vars;
	}
	
	public function plogin_rel_canonical(){
		global $wp_the_query;
 
		$vpage = get_query_var('vpage');
		if (!is_singular())
			return;
 
		if (!$id = $wp_the_query->get_queried_object_id())
			return;
 
		$link = trailingslashit(get_permalink($id));
 
		// Make sure fake pages' permalinks are canonical
		if (!empty($vpage))
			$link .= user_trailingslashit($vpage);
 
		echo "<link rel='canonical' href='$link' />\n";
	}
	
	public function vpage_template( $template = ''){
	    global $user_ID, $wp_the_query;
		
		$vpage = get_query_var('vpage');
 
		if (!empty($vpage)){
			get_currentuserinfo();
			if ($user_ID == ''){
				$template = plogin_get_vpage_template($vpage);
				if ($vpage == 'profile') $template = '';
			}
			else{
				$template = '';
				if ($vpage == 'profile') {
					$template = plogin_get_vpage_template($vpage);
					wp_enqueue_script( 'plogin-passmeter', $this->script_dir . '/password-strength-meter.js', array( 'jquery' ), '1.0' ); 
					wp_enqueue_script( 'plogin-userprofile', $this->script_dir . '/user-profile.js', array( 'jquery' ), '1.0' ); 
				}
			}
			if (!$template || empty($template)){
				wp_redirect(get_option('home') . '/404/');
				exit;
			}
			//$template = $this->template_themedir . '/' .$template;
			$template1 = $this->template_themedir .'/' .$template;
			$template2 = $this->template_dir .'/' .$template;
			if (file_exists($template1)) $template = $template1;
			else $template = $template2;
		}
		return $template;
	}
	
	public function plogin_ajax_process(){
		check_ajax_referer( 'plogin-action', 'security' );
		
		//Get the submitted parameters
		$user_login = $_REQUEST['user_login'];
		$user_option = $_REQUEST['user_option'];
		$userdata = array(
			'user_login' => $user_login,
			'user_password' => $_REQUEST['user_password'],
			'user_email' => $_REQUEST['user_email'],
			'first_name' => $_REQUEST['first_name'],
			'last_name' => $_REQUEST['last_name'],
			'password1' => $_REQUEST['password1'],
			'password2' => $_REQUEST['password2'],
			'remember' => esc_attr($_REQUEST['remember'])
		);

		// Get the user ID
		$user = get_user_by('login', $user_login);		
		if ($user) $userdata['ID'] = $user->ID;
		
		// Check for Secure Cookie
		$secure_cookie = '';

		switch ($user_option){
			case 'log in':
				$redirect_to = esc_attr($_REQUEST['redirect_to']);
				$retval = $this->login_user($userdata, $redirect_to);
				break;
			case 'register':
				$retval = $this->register_new_user($userdata);
				break;
			case 'get new password':
				$retval = $this->retrieve_password($user_login);
				break;
			case 'update profile':
				$retval = $this->update_user($userdata);
			default:
		}
	
		// Result
		$result = array();
		if ( !is_wp_error($retval) ) :
			$result['success'] = 1;
			if ($user_option == 'log in' ) $result['redirect'] = $retval;
			else $result['value'] = $this->process_message( $user_option );
		else :
			$result['success'] = 0;
			$result['value'] = $retval;
			$result['error'] = $retval->get_error_message();
		endif;
		
		header('content-type: application/json; charset=utf-8');

		echo $_GET['callback'] . '(' . json_encode($result) . ')';

		die();
	}
	
	public function process_message( $user_option ){
		switch ($user_option){
			case 'register':
				$message = __('Your registration has been successful.', 'plogin');
				break;
			case 'get new password':
				$message = __('Check your e-mail for your new password.', 'plogin');
				break;
			case 'update profile':
				$message = __('Your profile information has been updated.', 'plogin');
				break;
		}
		return $message;
	}
	
	public function login_user( $userdata, $redirect_to ){
		$creds = array();
		$creds['user_login'] = $userdata['user_login'];
		$creds['user_password'] = $userdata['user_password'];
		$creds['remember'] = $userdata['remember'];
	
		// Check for Secure Cookie
		$secure_cookie = '';
	
		// If the user wants ssl but the session is not ssl, force a secure cookie.
		if ( ! force_ssl_admin() ) {
			$user_name = sanitize_user( $creds['user_login'] );
			if ( $user = get_user_by('login',  $user_name ) ) {
				if ( get_user_option('use_ssl', $user->ID) ) {
					$secure_cookie = true;
					force_ssl_admin(true);
				}
			}
		}
			
		if ( force_ssl_admin() ) $secure_cookie = true;
		if ( $secure_cookie=='' && force_ssl_login() ) $secure_cookie = false;

		if( is_multisite() ){
			if ( $userdata['ID'] & !empty($creds['user_password']) ){
				if ( !plogin_is_registered_bloguser($userdata['ID']) ) return new WP_Error( 'incorrect_login', __( '<strong>ERROR</strong>: The username or password you entered is incorrect.' ) );
			}			
		}		
		
		// Login
		$user = wp_signon($creds, $secure_cookie);
	
		// Redirect filter
		if ( $secure_cookie && strstr($redirect_to, 'wp-admin') ) $redirect_to = str_replace('http:', 'https:', $redirect_to);

		if ( !is_wp_error($user) ) {		
			return $redirect_to;
		}else {
			if ( $user->errors ){
				if ( isset($user->errors['incorrect_password']) || isset($user->errors['invalid_username']) ){
					return new WP_Error( 'incorrect_login', __( '<strong>ERROR</strong>: The username or password you entered is incorrect.' ) );
				}
			}else{
					$user->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter your username and password to login.' , 'plogin') );
			}
			return $user;
		}
	}
	
	public function update_user($userdata){
		$current_user = wp_get_current_user();
		$errors = new WP_Error();
		
		if ($userdata['password1'] != $userdata['password2']) 
			$errors->add( 'mismatch_password', __( '<strong>ERROR</strong>: The passwords do no match.' , 'plogin') );

		if ( $errors->get_error_code())
			return $errors;
	
		$user_data = array(
			'ID' => $current_user->ID,
			'first_name' => $userdata['first_name'],
			'last_name' => $userdata['last_name'],
			'user_email' => $userdata['user_email']
		);
		if (strlen(trim($userdata['password1'])) > 0) $user_data['user_pass'] = $userdata['password1'];
		
		$user_id = wp_update_user( $user_data );
		if ( ! $user_id ) {
			$errors->add( 'updatefail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t update your profile... please contact the <a href="mailto:%s">webmaster</a> !' ), get_option( 'admin_email' ) ) );
			return $errors;
		}
		
		return $user_id;
	}
	
	public function register_new_user( $userdata ){
		$errors = new WP_Error();
	
		$user_login = $userdata['user_login'];
		$user_email = $userdata['user_email'];
		$user_password = $userdata['user_password'];
	
		// Check the username
		$sanitized_user_login = sanitize_user($user_login);
		if ( $sanitized_user_login == '' ) {
			$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter a username.' , 'plogin' ) );
		} elseif ( ! validate_username( $user_login ) ) {
			$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.' , 'plogin' ) );
		} elseif ( username_exists( $sanitized_user_login ) ) {
			if (is_multisite()) {
				if ( plogin_is_registered_bloguser($userdata['ID']) ) $errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered, please choose another one.' , 'plogin' ) );				
				else $errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered, please contact the Administrator to add the user to this blog.' , 'plogin' ) );
			}else
				$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered, please choose another one.' , 'plogin' ) );
		}
		
		// Check the e-mail address
		if ( $user_email == '' ) {
			$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please type your e-mail address.' , 'plogin' ) );
		} elseif ( ! is_email( $user_email ) ) {
			$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The email address is not correct.', 'plogin' ) );
			$user_email = '';
		} elseif ( email_exists( $user_email ) ) {
			$errors->add( 'email_exists', __( '<strong>ERROR</strong>: This email is already registered, please choose another one.', 'plogin' ) );
		}
	
		// Check the user password
		if ( trim($user_password) == '' ){
			$errors->add( 'empty_password', __( '<strong>ERROR</strong>: Please type your user password.', 'plogin' ) );
		} elseif (strlen(trim($user_password)) < 7){
			$errors->add( 'password_length', __('<strong>ERROR</strong>: The password should be at least seven characters long', 'plogin' ) );
		}
	
		if ( $errors->get_error_code())
			return $errors;
			
		/*$user_pass = wp_generate_password( 12, false);
		$user_id = wp_create_user( $sanitized_user_login, $user_pass, $user_email );
		if ( ! $user_id ) {
			$proc_errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !' ), get_option( 'admin_email' ) ) );
			return $proc_errors;
		}

		update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.

		wp_new_user_notification( $user_id, $user_pass );*/

		$user_data = array(
			'user_login' => $user_login,
			'first_name' => $userdata['first_name'],
			'last_name' => $userdata['last_name'],
			'user_pass' => $user_password,
			'user_email' => $user_email
		);
		$user_id = wp_insert_user( $user_data );
		if ( ! $user_id ) {
			$errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !' ), get_option( 'admin_email' ) ) );
			return $errors;
		}
		
		return $user_id;		
	}
	
	public function retrieve_password( $user ){
	
		global $wpdb, $current_site;

		$errors = new WP_Error();

		if ( empty( $user ) ) {
			$errors->add('empty_username', __('<strong>ERROR</strong>: Enter a username or e-mail address.'));
		} else if ( strpos( $user, '@' ) ) {
			$user_data = get_user_by( 'email', trim( $user ) );
			if ( empty( $user_data ) )
				$errors->add('invalid_email', __('<strong>ERROR</strong>: There is no user registered with that email address.'));
		} else {
			$login = trim($user);
			$user_data = get_user_by('login', $login);
		}

		if ( $errors->get_error_code() )
			return $errors;

		if ( !$user_data ) {
			$errors->add('invalidcombo', __('<strong>ERROR</strong>: Invalid username or e-mail.'));
			return $errors;
		}

		// redefining user_login ensures we return the right case in the email
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		$allow = apply_filters('allow_password_reset', true, $user_data->ID);

		if ( ! $allow )
			return new WP_Error('no_password_reset', __('Password reset is not allowed for this user'));
		else if ( is_wp_error($allow) )
			return $allow;

		$key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login));
		if ( empty($key) ) {
			// Generate something random for a key...
			$key = wp_generate_password(20, false);
			do_action('retrieve_password_key', $user_login, $key);
			// Now insert the new md5 key into the db
			$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
		}
		$message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
		$message .= network_site_url() . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
		$message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
		$message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
		$message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . "\r\n";

		if ( is_multisite() )
			$blogname = $GLOBALS['current_site']->site_name;
		else
			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$title = sprintf( __('[%s] Password Reset'), $blogname );

		$title = apply_filters('retrieve_password_title', $title);
		$message = apply_filters('retrieve_password_message', $message, $key);

		if ( $message && !wp_mail($user_email, $title, $message) ) {
			$errors->add('sent_error', __('<strong>ERROR</strong>: The e-mail could not be sent. Possible reason: your host may have disabled the mail() function...', 'plogin'));
			return $errors;
		}
		
		return true;
	}

	/*function plogin_flushrules(){
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}*/	
	
	function plogin_uninstaller(){
		$options = plogin_get_default_options();
		foreach ( $options as $key => $value )
			delete_option( $key, $value );
	}

}

$plogin = new pLogin();

//activation
register_activation_hook( __FILE__, flush_rewrite_rules );
		
//deactivation
register_deactivation_hook( __FILE__, flush_rewrite_rules );

// uninstalling the plugin
register_uninstall_hook( __FILE__, array('pLogin','plogin_uninstaller') );

endif; // end class
?>