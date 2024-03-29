<?php

class BW_GoogleAuthenticator {

static $instance; // to store a reference to the plugin, allows other plugins to remove actions
public $name;

/**
 * Constructor, entry point of the plugin
 */
function __construct() {
    self::$instance = $this;
    // add_action( 'init', array( $this, 'init' ) );
    if(bwd_get_option('bitwize')){
    	$this->name = 'Bitwize';
    } else {
    	$this->name = 'SellandSell';
    }

    $this->init();
}

/**
 * Initialization, Hooks, and localization
 */
function init() {
    require_once( 'base32.php' );

    add_action( 'login_form', array( $this, 'loginform' ) );
    add_action( 'login_footer', array( $this, 'loginfooter' ) );
    add_filter( 'authenticate', array( $this, 'check_otp' ), 50, 3 );

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        add_action( 'wp_ajax_GoogleAuthenticator_action', array( $this, 'ajax_callback' ) );
    }

    add_action( 'personal_options_update', array( $this, 'personal_options_update' ) );
    add_action( 'profile_personal_options', array( $this, 'profile_personal_options' ) );
    add_action( 'edit_user_profile', array( $this, 'edit_user_profile' ) );
    add_action( 'edit_user_profile_update', array( $this, 'edit_user_profile_update' ) );

	add_action('admin_enqueue_scripts', array($this, 'add_qrcode_script'));
}


/**
 * Check the verification code entered by the user.
 */
function verify( $secretkey, $thistry, $relaxedmode, $lasttimeslot ) {

	// Did the user enter 6 digits ?
	if ( strlen( $thistry ) != 6) {
		return false;
	} else {
		$thistry = intval ( $thistry );
	}

	// If user is running in relaxed mode, we allow more time drifting
	// �4 min, as opposed to � 30 seconds in normal mode.
	if ( $relaxedmode == 'enabled' ) {
		$firstcount = -8;
		$lastcount  =  8;
	} else {
		$firstcount = -1;
		$lastcount  =  1;
	}

	$tm = floor( time() / 30 );

	$secretkey=BW_GA_Base32::decode($secretkey);
	// Keys from 30 seconds before and after are valid aswell.
	for ($i=$firstcount; $i<=$lastcount; $i++) {
		// Pack time into binary string
		$time=chr(0).chr(0).chr(0).chr(0).pack('N*',$tm+$i);
		// Hash it with users secret key
		$hm = hash_hmac( 'SHA1', $time, $secretkey, true );
		// Use last nipple of result as index/offset
		$offset = ord(substr($hm,-1)) & 0x0F;
		// grab 4 bytes of the result
		$hashpart=substr($hm,$offset,4);
		// Unpak binary value
		$value=unpack("N",$hashpart);
		$value=$value[1];
		// Only 32 bits
		$value = $value & 0x7FFFFFFF;
		$value = $value % 1000000;
		if ( $value === $thistry ) {
			// Check for replay (Man-in-the-middle) attack.
			// Since this is not Star Trek, time can only move forward,
			// meaning current login attempt has to be in the future compared to
			// last successful login.
			if ( $lasttimeslot >= ($tm+$i) ) {
				error_log("Google Authenticator plugin: Man-in-the-middle attack detected (Could also be 2 legit login attempts within the same 30 second period)");
				return false;
			}
			// Return timeslot in which login happened.
			return $tm+$i;
		}
	}
	return false;
}

/**
 * Create a new random secret for the Google Authenticator app.
 * 16 characters, randomly chosen from the allowed Base32 characters
 * equals 10 bytes = 80 bits, as 256^10 = 32^16 = 2^80
 */
function create_secret() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // allowed characters in Base32
    $secret = '';
    for ( $i = 0; $i < 16; $i++ ) {
        $secret .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
    }
    return $secret;
}

/**
 * Add the script to generate QR codes.
 */
function add_qrcode_script() {
    wp_enqueue_script('jquery');
    wp_register_script('qrcode_script', plugins_url('jquery.qrcode.min.js', __FILE__),array("jquery"));
    wp_enqueue_script('qrcode_script');
}

/**
 * Add verification code field to login form.
 */
function loginform() {
    echo "\t<p>\n";
    echo "\t\t<label title=\"".__('If you don\'t have Google Authenticator enabled for your account, leave this field empty.',BW_TD)."\">".__('Google Authenticator code',BW_TD)."<span id=\"google-auth-info\"></span><br />\n";
    echo "\t\t<input type=\"text\" name=\"googleotp\" id=\"user_email\" class=\"input\" value=\"\" size=\"20\" style=\"ime-mode: inactive;\" /></label>\n";
    echo "\t</p>\n";
}

/**
 * Disable autocomplete on Google Authenticator code input field.
 */
function loginfooter() {
    echo "\n<script type=\"text/javascript\">\n";
    echo "\ttry{\n";
    echo "\t\tdocument.getElementById('user_email').setAttribute('autocomplete','off');\n";
    echo "\t} catch(e){}\n";
    echo "</script>\n";
}

/**
 * Login form handling.
 * Check Google Authenticator verification code, if user has been setup to do so.
 * @param wordpressuser
 * @return user/loginstatus
 */
function check_otp( $user, $username = '', $password = '' ) {
	// Store result of loginprocess, so far.
	$userstate = $user;

	// Get information on user, we need this in case an app password has been enabled,
	// since the $user var only contain an error at this point in the login flow.
	$user = get_user_by( 'login', $username );

	// Does the user have the Google Authenticator enabled ?
	if ( isset( $user->ID ) && trim(get_user_option( 'googleauthenticator_enabled', $user->ID ) ) == 'enabled' ) {

		// Get the users secret
		$GA_secret = trim( get_user_option( 'googleauthenticator_secret', $user->ID ) );

		// Figure out if user is using relaxed mode ?
		$GA_relaxedmode = trim( get_user_option( 'googleauthenticator_relaxedmode', $user->ID ) );

		// Get the verification code entered by the user trying to login
		if ( !empty( $_POST['googleotp'] )) { // Prevent PHP notices when using app password login
			$otp = trim( $_POST[ 'googleotp' ] );
		} else {
			$otp = '';
		}
		// When was the last successful login performed ?
		$lasttimeslot = trim( get_user_option( 'googleauthenticator_lasttimeslot', $user->ID ) );
		// Valid code ?
		if ( $timeslot = $this->verify( $GA_secret, $otp, $GA_relaxedmode, $lasttimeslot ) ) {
			// Store the timeslot in which login was successful.
			update_user_option( $user->ID, 'googleauthenticator_lasttimeslot', $timeslot, true );
			return $userstate;
		} else {
			// No, lets see if an app password is enabled, and this is an XMLRPC / APP login ?
			if ( trim( get_user_option( 'googleauthenticator_pwdenabled', $user->ID ) ) == 'enabled' && ( defined('XMLRPC_REQUEST') || defined('APP_REQUEST') ) ) {
				$GA_passwords 	= json_decode(  get_user_option( 'googleauthenticator_passwords', $user->ID ) );
				$passwordhash	= trim($GA_passwords->{'password'} );
				$usersha1		= sha1( strtoupper( str_replace( ' ', '', $password ) ) );
				if ( $passwordhash == $usersha1 ) { // ToDo: Remove after some time when users have migrated to new format
					return new WP_User( $user->ID );
				  // Try the new version based on thee wp_hash_password	function
				} elseif (wp_check_password( strtoupper( str_replace( ' ', '', $password ) ), $passwordhash)) {
					return new WP_User( $user->ID );
				} else {
					// Wrong XMLRPC/APP password !
					return new WP_Error( 'invalid_google_authenticator_password', __( '<strong>ERROR</strong>: The Google Authenticator password is incorrect.', BW_TD ) );
				}
			} else {
				return new WP_Error( 'invalid_google_authenticator_token', __( '<strong>ERROR</strong>: The Google Authenticator code is incorrect or has expired.', BW_TD ) );
			}
		}
	}
	// Google Authenticator isn't enabled for this account,
	// just resume normal authentication.
	return $userstate;
}


/**
 * Extend personal profile page with Google Authenticator settings.
 */
function profile_personal_options() {
	global $user_id, $is_profile_page;

	// If editing of Google Authenticator settings has been disabled, just return
	$GA_hidefromuser = trim( get_user_option( 'googleauthenticator_hidefromuser', $user_id ) );
	if ( $GA_hidefromuser == 'enabled') return;

	$GA_secret			= trim( get_user_option( 'googleauthenticator_secret', $user_id ) );
	$GA_enabled			= trim( get_user_option( 'googleauthenticator_enabled', $user_id ) );
	$GA_relaxedmode		= trim( get_user_option( 'googleauthenticator_relaxedmode', $user_id ) );
	$GA_description		= trim( get_user_option( 'googleauthenticator_description', $user_id ) );
	$GA_pwdenabled		= trim( get_user_option( 'googleauthenticator_pwdenabled', $user_id ) );
	$GA_password		= trim( get_user_option( 'googleauthenticator_passwords', $user_id ) );

	// We dont store the generated app password in cleartext so there is no point in trying
	// to show the user anything except from the fact that a password exists.
	if ( $GA_password != '' ) {
		$GA_password = "XXXX XXXX XXXX XXXX";
	}

	// In case the user has no secret ready (new install), we create one.
	if ( '' == $GA_secret ) {
		$GA_secret = $this->create_secret();
	}

	if ( '' == $GA_description ) {
		$GA_tsurl = str_replace(array('http://', 'https://'), array('',''), site_url());
		$GA_description = $this->name . ' - ' . $GA_tsurl;
	}

	?>
	<h3><i class="fa fa-google" style="font-size:24px;"></i> Authenticator Settings</h3>

	<table class="form-table"> <tbody>
		<tr>
			<th scope="row"><?php _e( 'Active', BW_TD ); ?></th>
			<td><input name="GA_enabled" id="GA_enabled" class="tog" type="checkbox"" . checked( $GA_enabled, 'enabled', false ) . "/></td>
		</tr>
	<?php if ( $is_profile_page || IS_PROFILE_PAGE ) : ?>
		<tr>
			<th scope="row"><?php _e( 'Relaxed mode', BW_TD ); ?></th>
			<td><input name="GA_relaxedmode" id="GA_relaxedmode" class="tog" type="checkbox"" . checked( $GA_relaxedmode, 'enabled', false ) . "/><span class="description"><?php _e(' Relaxed mode allows for more time drifting on your phone clock (&#177;4 min).',BW_TD); ?></span></td>
		</tr>

		<tr>
			<th><label for="GA_description"><?php _e('Description',BW_TD); ?></label></th>
			<td><input name="GA_description" id="GA_description" value="<?php echo $GA_description;?>"  type="text" size="25" /><span class="description"><?php _e(' Description that you\'ll see in the Google Authenticator app on your phone.',BW_TD); ?></span><br /></td>
		</tr>

		<tr>
			<th><label for="GA_secret"><?php _e('Secret',BW_TD); ?></label></th>
			<td>
				<input name="GA_secret" id="GA_secret" value="<?php echo $GA_secret;?>" readonly="readonly"  type="text" size="25" />
				<input name="GA_newsecret" id="GA_newsecret" value="<?php _e("Create new secret",BW_TD); ?>"   type="button" class="button" />
				<input name="show_qr" id="show_qr" value="<?php _e("Show/Hide QR code",BW_TD); ?>"   type="button" class="button" onclick="ShowOrHideQRCode();" />
			</td>
		</tr>

		<tr>
			<th></th>
			<td>
				<div id="GA_QR_INFO" style="display: none" >
					<div id="GA_QRCODE"/> </div>
					<span class="description"><br/> <?php _e( 'Scan this with the Google Authenticator app.', BW_TD ); ?></span>
				</div>
			</td>
		</tr>
		<tr>
			<th>Download App</th>
			<td style="font-size:32px;">
			<a href="https://itunes.apple.com/en/app/google-authenticator/id388497605?mt=8" target="_blank"><i class="fa fa-apple"></i></a> &nbsp;
			<a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en" target="_blank"><i class="fa fa-google"></i></a> &nbsp;
			<a href="https://www.microsoft.com/en-us/store/apps/authenticator/9wzdncrfj3rj" target="_blank"><i class="fa fa-windows"></i></a>
			</td>
		</tr>
	<?php endif; ?>

	</tbody></table>
	<script type="text/javascript">
	var GAnonce='<?php echo wp_create_nonce('GoogleAuthenticatoraction'); ?>';

  	//Create new secret and display it
	jQuery('#GA_newsecret').bind('click', function() {
		// Remove existing QRCode
		jQuery('#GA_QRCODE').html("");
		var data=new Object();
		data['action']	= 'GoogleAuthenticator_action';
		data['nonce']	= GAnonce;
		jQuery.post(ajaxurl, data, function(response) {
  			jQuery('#GA_secret').val(response['new-secret']);
  			var qrcode="otpauth://totp/<?php echo $this->name; ?>:"+escape(jQuery('#GA_description').val())+"?secret="+jQuery('#GA_secret').val()+"&issuer=<?php echo $this->name; ?>";
			jQuery('#GA_QRCODE').qrcode(qrcode);
 			jQuery('#GA_QR_INFO').show('slow');
  		});
	});

	// If the user starts modifying the description, hide the qrcode
	jQuery('#GA_description').bind('focus blur change keyup', function() {
		// Only remove QR Code if it's visible
		if (jQuery('#GA_QR_INFO').is(':visible')) {
			jQuery('#GA_QR_INFO').hide('slow');
			jQuery('#GA_QRCODE').html("");
  		}
	});

	// Create new app password
	jQuery('#GA_createpassword').bind('click',function() {
		var data=new Object();
		data['action']	= 'GoogleAuthenticator_action';
		data['nonce']	= GAnonce;
		data['save']	= 1;
		jQuery.post(ajaxurl, data, function(response) {
  			jQuery('#GA_password').val(response['new-secret'].match(new RegExp(".{0,4}","g")).join(' '));
  			jQuery('#GA_passworddesc').show();
  		});
	});

	jQuery('#GA_enabled').bind('change',function() {
		GoogleAuthenticator_apppasswordcontrol();
	});

	jQuery(document).ready(function() {
		jQuery('#GA_passworddesc').hide();
		GoogleAuthenticator_apppasswordcontrol();
	});

	function GoogleAuthenticator_apppasswordcontrol() {
		if (jQuery('#GA_enabled').is(':checked')) {
			jQuery('#GA_pwdenabled').removeAttr('disabled');
			jQuery('#GA_createpassword').removeAttr('disabled');
		} else {
			jQuery('#GA_pwdenabled').removeAttr('checked')
			jQuery('#GA_pwdenabled').attr('disabled', true);
			jQuery('#GA_createpassword').attr('disabled', true);
		}
	}

	function ShowOrHideQRCode() {
		if (jQuery('#GA_QR_INFO').is(':hidden')) {
			var qrcode="otpauth://totp/<?php echo $this->name; ?>:"+escape(jQuery('#GA_description').val())+"?secret="+jQuery('#GA_secret').val()+"&issuer=<?php echo $this->name; ?>";
			jQuery('#GA_QRCODE').qrcode(qrcode);
	        jQuery('#GA_QR_INFO').show('slow');
		} else {
			jQuery('#GA_QR_INFO').hide('slow');
			jQuery('#GA_QRCODE').html("");
		}
	}
</script>
	<?php
}

/**
 * Form handling of Google Authenticator options added to personal profile page (user editing his own profile)
 */
function personal_options_update() {
	global $user_id;

	// If editing of Google Authenticator settings has been disabled, just return
	$GA_hidefromuser = trim( get_user_option( 'googleauthenticator_hidefromuser', $user_id ) );
	if ( $GA_hidefromuser == 'enabled') return;


	$GA_enabled	= ! empty( $_POST['GA_enabled'] );
	$GA_description	= trim( sanitize_text_field($_POST['GA_description'] ) );
	$GA_relaxedmode	= ! empty( $_POST['GA_relaxedmode'] );
	$GA_secret	= trim( $_POST['GA_secret'] );
	$GA_pwdenabled	= ! empty( $_POST['GA_pwdenabled'] );
	$GA_password	= str_replace(' ', '', trim( $_POST['GA_password'] ) );

	if ( ! $GA_enabled ) {
		$GA_enabled = 'disabled';
	} else {
		$GA_enabled = 'enabled';
	}

	if ( ! $GA_relaxedmode ) {
		$GA_relaxedmode = 'disabled';
	} else {
		$GA_relaxedmode = 'enabled';
	}


	if ( ! $GA_pwdenabled ) {
		$GA_pwdenabled = 'disabled';
	} else {
		$GA_pwdenabled = 'enabled';
	}

	// Only store password if a new one has been generated.
	if (strtoupper($GA_password) != 'XXXXXXXXXXXXXXXX' ) {
		// Store the password in a format that can be expanded easily later on if needed.
		$GA_password = array( 'appname' => 'Default', 'password' => wp_hash_password( $GA_password ) );
		update_user_option( $user_id, 'googleauthenticator_passwords', json_encode( $GA_password ), true );
	}

	update_user_option( $user_id, 'googleauthenticator_enabled', $GA_enabled, true );
	update_user_option( $user_id, 'googleauthenticator_description', $GA_description, true );
	update_user_option( $user_id, 'googleauthenticator_relaxedmode', $GA_relaxedmode, true );
	update_user_option( $user_id, 'googleauthenticator_secret', $GA_secret, true );
	update_user_option( $user_id, 'googleauthenticator_pwdenabled', $GA_pwdenabled, true );

}

/**
 * Extend profile page with ability to enable/disable Google Authenticator authentication requirement.
 * Used by an administrator when editing other users.
 */
function edit_user_profile() {
	global $user_id;
	$GA_enabled      = trim( get_user_option( 'googleauthenticator_enabled', $user_id ) );
	$GA_hidefromuser = trim( get_user_option( 'googleauthenticator_hidefromuser', $user_id ) );
	?>
	<h3><?php _e('Google Authenticator Settings',BW_TD);?></h3>
	<table class="form-table">
		<tbody>

		<tr>
			<th scope="row"><?php _e('Hide settings from user',BW_TD); ?></th>
			<td>
				<input name="GA_hidefromuser" id="GA_hidefromuser"  class="tog" type="checkbox" <?php checked( $GA_hidefromuser, 'enabled', false ); ?> />
			</td>
		</tr>

		<tr>
			<th scope="row"><?php _e('Active',BW_TD); ?></th>
			<td>
				<input name="GA_enabled" id="GA_enabled"  class="tog" type="checkbox" <?php checked( $GA_enabled, 'enabled', false ); ?> />
			</td>
		</tr>

		</tbody>
	</table>
	<?php
}

/**
 * Form handling of Google Authenticator options on edit profile page (admin user editing other user)
 */
function edit_user_profile_update() {
	global $user_id;

	$GA_enabled	     = ! empty( $_POST['GA_enabled'] );
	$GA_hidefromuser = ! empty( $_POST['GA_hidefromuser'] );

	if ( ! $GA_enabled ) {
		$GA_enabled = 'disabled';
	} else {
		$GA_enabled = 'enabled';
	}

	if ( ! $GA_hidefromuser ) {
		$GA_hidefromuser = 'disabled';
	} else {
		$GA_hidefromuser = 'enabled';
	}

	update_user_option( $user_id, 'googleauthenticator_enabled', $GA_enabled, true );
	update_user_option( $user_id, 'googleauthenticator_hidefromuser', $GA_hidefromuser, true );

}


/**
* AJAX callback function used to generate new secret
*/
function ajax_callback() {
	global $user_id;

	// Some AJAX security.
	check_ajax_referer( 'GoogleAuthenticatoraction', 'nonce' );

	// Create new secret.
	$secret = $this->create_secret();

	$result = array( 'new-secret' => $secret );
	header( 'Content-Type: application/json' );
	echo json_encode( $result );

	// die() is required to return a proper result
	die();
}

} // end class

$BW_google_authenticator = new BW_GoogleAuthenticator;
?>
