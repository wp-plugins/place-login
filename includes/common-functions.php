<?php
function plogin_get_url_vpage($slug){
	$urlpage = get_permalink(get_option('plogin_parent_page'));
	if (strpos($urlpage,'page_id'))
		return $urlpage .'&vpage=' .$slug;
	else{
		if ($urlpage[strlen($urlpage)-1] == '/') return $urlpage .$slug;
		else return $urlpage .'/' .$slug;
	}
	//return get_permalink(get_option('plogin_parent_page')) .$slug;
}

function plogin_get_first_page(){
	$pages = get_pages(); 
	return $pages[0]->ID;
}

function plogin_get_page_slug($id){
	$page = get_page($id);
	return $page->post_name;
}

function plogin_is_registered_bloguser($user_id){
	global $current_blog;
	$blog = get_blog_details($current_blog->blog_id);
	$site_path = $blog->path;

	// Check if the user is registered in the current path site
	$user_blogs = get_blogs_of_user( $user_id );
	$registered = false;
	foreach ($user_blogs AS $user_blog) {
		if ($site_path == $user_blog->path) $registered = true;
	}
	return $registered;
}

function plogin_get_default_options(){
	$options = array(
		'plogin_after_login' => '',
		'plogin_after_logout' => '',
		'plogin_parent_page' => plogin_get_first_page(),
		'plogin_register' => '',
		'plogin_lostpwd' => '',
		'plogin_links' => '<a href="%PROFILE%">Profile</a>'
	);
	return $options;
}

function plogin_current_url() {
	$vpage = get_query_var('vpage');
	
	$pageURL  = 'http://';
	$pageURL .= $_SERVER['HTTP_HOST'];	
	$pageURL .= $_SERVER['REQUEST_URI'];	
	if (!empty($vpage)) $pageURL = str_replace($vpage,'',$pageURL);
		
	if ( force_ssl_admin() ) $pageURL = str_replace( 'http:', 'https:', $pageURL );
	
	return $pageURL;	
}

function plogin_get_vpage_template($vpage){
	global $plogin;
	
	$template = '';
	foreach($plogin->virtual_pages as $slug => $content){
		if ($vpage == $slug) {
			foreach ($content as $key=>$value){
				if ($key == 'template') $template = $value;
			}
		}
	}
	return $template;
}

function plogin_form(){
	global $plogin, $current_user;
	
	get_currentuserinfo();	
	
	if ($current_user->ID != ''){
		echo '<div class="display-user">';
		plogin_user_options($current_user->ID, $current_user->display_name);
		echo '</div>';
		}
	else {
		echo '<div id="dialog-login" title="Login">';
		plogin_login_form();
		echo '</div>
	<script>
		jQuery("#plogin-loginform #wp-submit").hide();
	</script>		  
	<button id="btn-login">Login</button>';
	}
}

function plogin_user_options($user_ID, $name){
	//if (get_option('sidebar_login_avatar')=='1') 
	echo '<div id="menubox">
		  <div class="avatar_container">'.get_avatar($user_ID, $size = '35').'
		  </div><a class="parent">' .$name .' &#187;</a>
		  <div id="dropdown">
		  <ul class="children">';
	$loggedinlinks = trim(get_option('plogin_links'));
	$links = explode("\n", $loggedinlinks);
	if (sizeof($links)>0)
		foreach ($links as $l) {
			$l = trim($l);
			if (!empty($l)) {
				$link = explode('|',$l);
				if (isset($link[1])) {
					$cap = strtolower(trim($link[1]));
					if ($cap=='true') {
						if (!current_user_can( 'manage_options' )) continue;
					} else {
						if (!current_user_can( $cap )) continue;
					}
				}
				// Parse %USERNAME%
				$link[0] = str_replace('%USERNAME%',$name,$link[0]);
				$link[0] = str_replace('%username%',$name,$link[0]);
				// Parse %USERID%
				$link[0] = str_replace('%USERID%',$user_ID,$link[0]);
				$link[0] = str_replace('%userid%',$user_ID,$link[0]);
				// Parse %PROFILE%
				$link[0] = str_replace('%PROFILE%',plogin_get_url_vpage('profile'),$link[0]);
				$link[0] = str_replace('%profile%',plogin_get_url_vpage('profile'),$link[0]);
				echo '<li>'.$link[0].'</li>';
			}
		}

	$logouturl = get_option('plogin_after_logout');
	if (!$logouturl || empty($logouturl)) $logouturl = plogin_current_url();
	echo '<li><a href="' .wp_logout_url($logouturl) .'" title="Logout">Logout</a></li>
		  </ul>
		  </div>
		  </div>';
}

function plogin_profile_form(){
	$current_user = wp_get_current_user(); 
?>
	<div class="plogin-form">
		<h1>Edit Profile</h1>
		<form id="plogin-editprofileform" action="" method="post">
			<p>
				<label for="user_login"><?php _e('Username', 'plogin') ?><br />
				<input type="text" name="log" id="user_login" value="<?php echo esc_attr($current_user->user_login); ?>" disabled="disabled" class="regular-text" /> <span class="description"><?php _e('Usernames cannot be changed.'); ?></span></label>
			</p>
			<p>
				<label for="first_name"><?php _e('First name', 'plogin') ?><br />
				<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr(stripslashes($current_user->first_name)); ?>" size="25" tabindex="20" /></label>
			</p>
			<p>
				<label for="last_name"><?php _e('Last name', 'plogin') ?><br />
				<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr(stripslashes($current_user->last_name)); ?>" size="25" tabindex="20" /></label>
			</p>
			<p>
				<label for="user_email"><?php _e('E-mail', 'plogin') ?><br />
				<input type="email" name="email" id="user_email" value="<?php echo esc_attr(stripslashes($current_user->user_email)); ?>" size="25" tabindex="30" /></label>
			</p>
			<p>
				<label for="pass1"><?php _e('New password:','plogin') ?><br />
				<input type="password" name="pass1" id="pass1" size="20" value="" autocomplete="off" tabindex="40" />
				<span class="description"><?php _e('If you would like to change the password type a new one; otherwise, leave it blank','plogin'); ?></span></label>
			</p>
			<p>
				<label for="pass2"><?php _e('Confirm new password:','plogin') ?><br />
				<input type="password" name="pass2" id="pass2" size="20" value="" autocomplete="off" tabindex="50"  />
				<span class="description"><?php _e('Type the new password again','plogin'); ?></span></label><br />
				<div class="pass-check">
				<div id="pass-strength-result"><?php _e('Strength indicator','plogin'); ?></div>
				<p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).','plogin'); ?></p></div>
			</p>
			<br>
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Update Profile', 'plogin'); ?>" tabindex="100" />
			</p>
		</form>
	</div>
<?php
}
function plogin_login_form(){
?>
	<div class="plogin-form">
	<form id="plogin-loginform" action="" method="post">
		<p>
			<label for="user_login"><?php _e('Username:','plogin') ?></label><br />
			<input type="text" name="log" id="user_login" value="<?php echo esc_attr($_POST['user_login']); ?>" size="20" />
		</p>
		<p>
			<label for="user_pass"><?php _e('Password:','plogin') ?></label><br />
			<input type="password" name="pwd" id="user_pass" value="" size="20" />
		</p>
	<?php 
		$rememberme = ! empty( $_POST['rememberme'] );
		$redirect_to = get_option('plogin_after_login');
		if (!$redirect_to || empty($redirect_to)) $redirect_to = plogin_current_url();
	?>
		<p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever" <?php checked( $rememberme ); ?> /> <?php esc_attr_e('Remember Me'); ?></label></p>
		<p class="submit">
			<input type="submit" name="wp-submit" id="wp-submit" value="<?php esc_attr_e('Log In'); ?>" />
			<input type="hidden" name="redirect_to" class="redirect_to" value="<?php echo $redirect_to; ?>" />
			<input type="hidden" name="testcookie" value="1" />
		</p>
	</form>
	<?php 
		$links = '';
		if (get_option('users_can_register')){ 
			$pageurl = get_option('plogin_register');
			if (!$pageurl || empty($pageurl)) $pageurl = plogin_get_url_vpage('register');
			$links = '<li><a href="' .$pageurl .'" rel="nofollow">'.__('Register','plogin').'</a></li>';
		}
		$pageurl = get_option('plogin_lostpwd');
		if (!$pageurl || empty($pageurl)) $pageurl = plogin_get_url_vpage('lostpwd');
		$links .= '<li><a href="' .$pageurl .'" rel="nofollow">' .__('Lost Password?','plogin') .'</a></li>';
		if ($links) echo '<ul>'.$links.'</ul>';	
	?>
	</div>
<?php
}
function plogin_register_form(){
?>
	<div class="plogin-form">
		<h1>Registration</h1>
		<form id="plogin-registerform" action="" method="post">
			<p>
				<label for="user_login"><?php _e('Username <em>(required)</em>', 'plogin') ?><br />
				<input type="text" name="log" id="user_login" value="<?php if (isset($_POST['user_login'])) echo esc_attr(stripslashes($_POST['user_login'])); ?>" size="20" tabindex="10" /></label>
			</p>
			<p>
				<label for="first_name"><?php _e('First name', 'plogin') ?><br />
				<input type="text" name="first_name" id="first_name" value="<?php if (isset($_POST['first_name'])) echo esc_attr(stripslashes($_POST['first_name'])); ?>" size="25" tabindex="20" /></label>
			</p>
			<p>
				<label for="last_name"><?php _e('Last name', 'plogin') ?><br />
				<input type="text" name="last_name" id="last_name" value="<?php if (isset($_POST['last_name'])) echo esc_attr(stripslashes($_POST['last_name'])); ?>" size="25" tabindex="30" /></label>
			</p>
			<p>
				<label for="user_email"><?php _e('E-mail <em>(required)</em>') ?><br />
				<input type="email" name="email" id="user_email" value="<?php if (isset($_POST['user_email'])) echo esc_attr(stripslashes($_POST['user_email'])); ?>" size="25" tabindex="40" /></label>
			</p>
			<p>
				<label for="user_pass"><?php _e('Password <em>(required)</em>', 'plogin') ?><br />
				<input type="password" name="pwd" id="user_pass" value="<?php if (isset($_POST['user_pass'])) echo esc_attr(stripslashes(trim($_POST['user_pass']))); ?>" size="20" tabindex="50" />
				<span class="description"><?php _e('7 characters minimum, no spaces. Case sensitive','plogin'); ?></span></label>
			</p>
			<br>
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Register', 'plogin'); ?>" tabindex="100" />
			</p>
		</form>
	</div>
<?php
}
function plogin_lostpwd_form(){
?>
	<div class="plogin-form">
		<h1>Password reset</h1>
		<form id="plogin-lostpasswordform" action="" method="post">
			<p>
				<label for="user_login" ><?php _e('Username or E-mail:', 'plogin') ?><br />
				<input type="text" name="log" id="user_login" value="<?php if (isset($_POST['user_login'])) echo esc_attr(stripslashes($_POST['user_login'])); ?>" size="20" tabindex="10" /></label>
			</p>
			<br>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" value="<?php esc_attr_e('Get New Password'); ?>" tabindex="100" />
			</p>
		</form>
	</div>
<?php
}
?>