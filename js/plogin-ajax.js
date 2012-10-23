jQuery(function(){
	function execute() {
		alert('This is Ok button');
	}
	
	function close() {
		jQuery('.login_error').remove();
		jQuery(this).dialog("close");
	}

	function signin(){
		var thisform = this;

		if ( jQuery('input[name="rememberme"]:checked', thisform ).size() > 0 ) {
			remember = jQuery('input[name="rememberme"]:checked', thisform ).val();
		} else {
			remember = '';
		}
		user_option = jQuery('input[name="wp-submit"]', thisform).val().toLowerCase();
    
		var data = {
			action: 		'plogin_process',
			user_login:		jQuery('input[name="log"]', thisform).val(),
			user_password:	jQuery('input[name="pwd"]', thisform).val(),
			user_email:		jQuery('input[name="email"]', thisform).val(),
			first_name:		jQuery('input[name="first_name"]', thisform).val(),
			last_name:		jQuery('input[name="last_name"]', thisform).val(),
			password1:		jQuery('input[name="pass1"]', thisform).val(),
			password2:		jQuery('input[name="pass2"]', thisform).val(),
			user_option:	user_option,
			remember:		remember,
			redirect_to:	jQuery('.redirect_to:eq(0)', thisform).val(),
			security: 		plogin_params.login_nonce
		};
		// Ajax action
		jQuery.ajax({
			url: plogin_params.ajax_url,
			data: data,
			type: 'GET',
			dataType: 'jsonp',
			success: function( result ) {
				jQuery('.login_error').remove();
				if (result.success==1) {
					if (result.redirect==null || result.redirect==""){
						jQuery(thisform).prepend('<p class="valid">' + result.value + '</p>');
						jQuery('p label').hide();
						jQuery('.pass-check').hide();
						jQuery('.submit').hide();
					} else {
						window.location = result.redirect;
						//alert(result.redirect);
					}
				} else {
					jQuery(thisform).prepend('<p class="login_error">' + result.error + '</p>');
					//jQuery(thisform).unblock();
					//jQuery(thisform).prepend('<p class="login_error">Failed</p>');
				}
			}
			
		});
		return false;			
	}

	var dialogOpts = {
		autoOpen: false,
		modal: true,
		dialogClass: 'no-close',
		buttons: {
			'Ok' : signin,
			'Cancel': close
		}
	};

      jQuery( "#dialog-login" ).dialog(dialogOpts);
	jQuery( "#btn-login" ).button().click(function(){
		jQuery("#dialog-login").dialog("open");
	});

	// Ajax Custom Sidebar Login Form
	jQuery('.plogin-form form').bind({submit: signin});

});
