<?php
class BW_SMTP{
	public function __construct(){
		add_action( 'phpmailer_init', array($this, 'init_smtp'));
		add_action( 'admin_menu', array($this, 'admin_default_setup' ));
		add_action( 'admin_init', array($this, 'admin_init' ));
	}

	function get_option(){
		return get_option( 'bw_smtp_options' );
	}

	function register_settings() {
		$options_default = array(
			'from_email_field' => '',
			'from_name_field' => '',
			'smtp_settings' => array(
				'host' => '',
				'type_encryption' => 'none',
				'port' => 25,
				'autentication' => 'yes',
				'username' => '',
				'password' => ''
			)
		);

		/* install the default plugin options */
        if ( ! get_option( 'bw_smtp_options' ) ){
            add_option( 'bw_smtp_options', $options_default, '', 'yes' );
        }
	}

	function admin_default_setup() {
        add_options_page( 'SMTP', 'SMTP', 'manage_options', 'bw_smtp_settings', array($this,'settings'));
	}

	function admin_init() {
		if ( isset( $_REQUEST['page'] ) && 'bw_smtp_settings' == $_REQUEST['page'] ) {
			$this->register_settings();
		}
	}

	function init_smtp( $phpmailer ) {

        if(!$this->credentials_configured()) return;

		$options = $this->get_option();

		$phpmailer->IsSMTP();
		$from_email = apply_filters('smtp_from_email', $options['from_email_field']);
        $phpmailer->From = $from_email;
        $from_name  = apply_filters('smtp_from_name', $options['from_name_field']);
        $phpmailer->FromName = $from_name;
        $phpmailer->SetFrom($phpmailer->From, $phpmailer->FromName);

		if ( $options['smtp_settings']['type_encryption'] !== 'none' ) {
			$phpmailer->SMTPSecure = $options['smtp_settings']['type_encryption'];
		}

		$phpmailer->Host = $options['smtp_settings']['host'];
		$phpmailer->Port = $options['smtp_settings']['port'];

		if( 'yes' == $options['smtp_settings']['autentication'] ){
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $options['smtp_settings']['username'];
			$phpmailer->Password = $options['smtp_settings']['password'];
		}
		$phpmailer->SMTPAutoTLS = false;
	}

	function credentials_configured() {
        $options = $this->get_option();
        $credentials_configured = true;
        if(!isset($options['from_email_field']) || empty($options['from_email_field'])){
            $credentials_configured = false;
        }
        if(!isset($options['from_name_field']) || empty($options['from_name_field'])){
            $credentials_configured = false;;
        }
        if(!isset($options['smtp_settings']['host']) || empty($options['smtp_settings']['host'])){
            $credentials_configured = false;;
        }
        return $credentials_configured;
    }

    function test_mail( $to_email, $subject, $message ) {

    	// if(wp_mail($to_email, $subject, $message)){
    	// 	return 'Test mail was sent successfully';
    	// } else {
    	// 	return 'Error occured, Could not connect';
    	// }

        if(!$this->credentials_configured()) return;

		$errors = '';

		$options = $this->get_option();

		require_once( ABSPATH . WPINC . '/class-phpmailer.php' );
		$mail = new PHPMailer();

        $charset = get_bloginfo( 'charset' );
		$mail->CharSet = $charset;

		$from_name  = $options['from_name_field'];
		$from_email = $options['from_email_field'];

		$mail->IsSMTP();

		/* If using smtp auth, set the username & password */
		if( 'yes' == $options['smtp_settings']['autentication'] ){
			$mail->SMTPAuth = true;
			$mail->Username = $options['smtp_settings']['username'];
			$mail->Password = $options['smtp_settings']['password'];
		}

		/* Set the SMTPSecure value, if set to none, leave this blank */
		if ( $options['smtp_settings']['type_encryption'] !== 'none' ) {
			$mail->SMTPSecure = $options['smtp_settings']['type_encryption'];
		}

        /* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
        $mail->SMTPAutoTLS = false;

		/* Set the other options */
		$mail->Host = $options['smtp_settings']['host'];
		$mail->Port = $options['smtp_settings']['port'];
		$mail->SetFrom( $from_email, $from_name );
		$mail->isHTML( true );
		$mail->Subject = $subject;
		$mail->MsgHTML( $message );
		$mail->AddAddress( $to_email );
		$mail->SMTPDebug = 3;

		/* Send mail and return result */
		if ( ! $mail->Send() )
			$errors = $mail->ErrorInfo;

		$mail->ClearAddresses();
		$mail->ClearAllRecipients();

		if ( ! empty( $errors ) ) {
			return $errors;
		}
		else{
			return 'Test mail was sent successfully';
		}
	}

	function settings() {
		$display_add_options = $message = $error = $result = '';

		$options = $this->get_option();

		if ( isset( $_POST['bwsmtp_form_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bwsmtp_nonce_name' ) ) {
			/* Update settings */
			$options['from_name_field'] = isset( $_POST['bwsmtp_from_name'] ) ? sanitize_text_field(wp_unslash($_POST['bwsmtp_from_name'])) : '';
			if( isset( $_POST['bwsmtp_from_email'] ) ){
				if( is_email( $_POST['bwsmtp_from_email'] ) ){
					$options['from_email_field'] = sanitize_email($_POST['bwsmtp_from_email']);
				}
				else{
					$error .= " Please enter a valid email address in the 'FROM' field.";
				}
			}

			$options['smtp_settings']['host'] = sanitize_text_field($_POST['bwsmtp_smtp_host']);
			$options['smtp_settings']['type_encryption'] = ( isset( $_POST['bwsmtp_smtp_type_encryption'] ) ) ? sanitize_text_field($_POST['bwsmtp_smtp_type_encryption']) : 'none' ;
			$options['smtp_settings']['autentication'] = ( isset( $_POST['bwsmtp_smtp_autentication'] ) ) ? sanitize_text_field($_POST['bwsmtp_smtp_autentication']) : 'yes' ;
			$options['smtp_settings']['username'] = sanitize_text_field($_POST['bwsmtp_smtp_username']);
			$options['smtp_settings']['password'] = trim($_POST['bwsmtp_smtp_password']);

			/* Check value from "SMTP port" option */
			if ( isset( $_POST['bwsmtp_smtp_port'] ) ) {
				if ( empty( $_POST['bwsmtp_smtp_port'] ) || 1 > intval( $_POST['bwsmtp_smtp_port'] ) || ( ! preg_match( '/^\d+$/', $_POST['bwsmtp_smtp_port'] ) ) ) {
					$options['smtp_settings']['port'] = '25';
					$error .= " Please enter a valid port in the 'SMTP Port' field.";
				} else {
					$options['smtp_settings']['port'] = sanitize_text_field($_POST['bwsmtp_smtp_port']);
				}
			}

			/* Update settings in the database */
			if ( empty( $error ) ) {
				update_option( 'bw_smtp_options', $options );
				$message .= "Settings saved.";
			}
			else{
				$error .= " Settings are not saved.";
			}
		}

		/* Send test letter */
		if ( isset( $_POST['bwsmtp_test_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bwsmtp_nonce_name' ) ) {
			if( isset( $_POST['bwsmtp_to'] ) ){
				if( is_email( $_POST['bwsmtp_to'] ) ){
					$bwsmtp_to =$_POST['bwsmtp_to'];
				}
				else{
					$error .= " Please enter a valid email address in the 'FROM' field.";
				}
			}
			$bwsmtp_subject = isset( $_POST['bwsmtp_subject'] ) ? $_POST['bwsmtp_subject'] : '';
			$bwsmtp_message = isset( $_POST['bwsmtp_message'] ) ? $_POST['bwsmtp_message'] : '';
			if( ! empty( $bwsmtp_to ) )
				$result = $this->test_mail( $bwsmtp_to, $bwsmtp_subject, $bwsmtp_message );
		} ?>
		<style>
			.bwsmtp_info {
			    font-size: 10px;
			    color: #888;
			}
			#bwsmtp_settings_form input[type='text'],
			input[type='password']{
			    width: 250px;
			}
			textarea#bwsmtp_message{
			    width: 250px;
			}
		</style>
		<script>
			(function( $ ){
				$( document ).ready( function() {
					$( '#bwsmtp-mail input' ).bind( "change select", function() {
						if ( $( this ).attr( 'type' ) != 'submit' ) {
							$( '.updated' ).css( 'display', 'none' );
							$( '#bwsmtp-settings-notice' ).css( 'display', 'block' );
						};
					});
					$('#bw_smtp_gmail').bind('click', function(e){
						e.preventDefault();
						var email = prompt("Please enter your email", "@gmail.com");
						if(email != null){
							var name = email.split("@")[0];
							$('input[name="bwsmtp_smtp_host"]').val('smtp.gmail.com');
							$('#bwsmtp_smtp_type_encryption_3').trigger('click');
							$('input[name="bwsmtp_smtp_port"]').val('587');
							$('#bwsmtp_smtp_autentication').trigger('click');
							$('input[name="bwsmtp_from_name"]').val(name);
							$('input[name="bwsmtp_from_email"]').val(email);
							$('input[name="bwsmtp_smtp_username"]').val(email);
							$('input[name="bwsmtp_smtp_password"]').focus();
						}
					});

					$('#bw_smtp_sparkpost').bind('click', function(e){
						e.preventDefault();
						var api = prompt("Please enter your API Key", "");
						if(api != null){
							$('input[name="bwsmtp_smtp_host"]').val('smtp.sparkpostmail.com');
							$('#bwsmtp_smtp_type_encryption_3').trigger('click');
							$('input[name="bwsmtp_smtp_port"]').val('587');
							$('#bwsmtp_smtp_autentication').trigger('click');
							$('input[name="bwsmtp_smtp_username"]').val('SMTP_Injection');
							$('input[name="bwsmtp_smtp_password"]').val(api);
						}
					});
				});
			})(jQuery);
		</script>
		<div class="bwsmtp-mail wrap" id="bwsmtp-mail">
			<div id="icon-options-general" class="icon32 icon32-bws"></div>
			<h2>SMTP Settings</h2>
			<p>
				<a href="#" id="bw_smtp_gmail">Setup Gmail</a> -
				<a href="#" id="bw_smtp_sparkpost">Setup SparkPost</a>
			</p>

			<div class="updated" <?php if( empty( $message ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $message; ?></strong></p>
			</div>
			<div class="error" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $error; ?></strong></p>
			</div>
			<div id="bwsmtp-settings-notice" class="updated" style="display:none">
				<p><strong>Notice:</strong> Settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.</p>
			</div>
			<h3>General Settings</h3>
			<form id="bwsmtp_settings_form" method="post" action="">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">From Email Address</th>
						<td>
							<input type="text" name="bwsmtp_from_email" value="<?php echo esc_attr( $options['from_email_field'] ); ?>"/><br />
							<span class="bwsmtp_info">This email address will be used in the 'From' field.</span>
					</td>
					</tr>
					<tr valign="top">
						<th scope="row">From Name</th>
						<td>
							<input type="text" name="bwsmtp_from_name" value="<?php echo esc_attr($options['from_name_field']); ?>"/><br />
							<span  class="bwsmtp_info">This text will be used in the 'FROM' field</span>
						</td>
					</tr>
					<tr class="ad_opt bwsmtp_smtp_options">
						<th>SMTP Host</th>
						<td>
							<input type='text' name='bwsmtp_smtp_host' value='<?php echo esc_attr($options['smtp_settings']['host']); ?>' /><br />
							<span class="bwsmtp_info">Your mail server
							<?php
								if(strpos($options['smtp_settings']['host'], 'gmail.com') !== false || strpos($options['smtp_settings']['host'], 'googlemail.com') !== false){
									echo '<br><a href="https://www.google.com/settings/security/lesssecureapps" target="_blank">Turn on "Access for less secure apps" for your email to ensure deliverability</a>';
								}
							?>
							</span>
						</td>
					</tr>
					<tr class="ad_opt bwsmtp_smtp_options">
						<th>Type of Encription</th>
						<td>
							<label for="bwsmtp_smtp_type_encryption_1"><input type="radio" id="bwsmtp_smtp_type_encryption_1" name="bwsmtp_smtp_type_encryption" value='none' <?php if( 'none' == $options['smtp_settings']['type_encryption'] ) echo 'checked="checked"'; ?> /> None</label>
							<label for="bwsmtp_smtp_type_encryption_2"><input type="radio" id="bwsmtp_smtp_type_encryption_2" name="bwsmtp_smtp_type_encryption" value='ssl' <?php if( 'ssl' == $options['smtp_settings']['type_encryption'] ) echo 'checked="checked"'; ?> /> SSL</label>
							<label for="bwsmtp_smtp_type_encryption_3"><input type="radio" id="bwsmtp_smtp_type_encryption_3" name="bwsmtp_smtp_type_encryption" value='tls' <?php if( 'tls' == $options['smtp_settings']['type_encryption'] ) echo 'checked="checked"'; ?> /> TLS</label><br />
							<span class="bwsmtp_info">For most servers SSL is the recommended option</span>
						</td>
					</tr>
					<tr class="ad_opt bwsmtp_smtp_options">
						<th>SMTP Port</th>
						<td>
							<input type='text' name='bwsmtp_smtp_port' value='<?php echo esc_attr($options['smtp_settings']['port']); ?>' /><br />
							<span class="bwsmtp_info">The port to your mail server</span>
						</td>
					</tr>
					<tr class="ad_opt bwsmtp_smtp_options">
						<th>SMTP Authentication</th>
						<td>
							<label for="bwsmtp_smtp_autentication"><input type="radio" id="bwsmtp_smtp_no_autentication" name="bwsmtp_smtp_autentication" value='no' <?php if( 'no' == $options['smtp_settings']['autentication'] ) echo 'checked="checked"'; ?> /> No</label>
							<label for="bwsmtp_smtp_autentication"><input type="radio" id="bwsmtp_smtp_autentication" name="bwsmtp_smtp_autentication" value='yes' <?php if( 'yes' == $options['smtp_settings']['autentication'] ) echo 'checked="checked"'; ?> /> Yes</label><br />
							<span class="bwsmtp_info">This options should always be checked 'Yes'</span>
						</td>
					</tr>
					<tr class="ad_opt bwsmtp_smtp_options">
						<th>SMTP username</th>
						<td>
							<input type='text' name='bwsmtp_smtp_username' value='<?php echo esc_attr($options['smtp_settings']['username']); ?>' /><br />
							<span class="bwsmtp_info">The username to login to your mail server</span>
						</td>
					</tr>
					<tr class="ad_opt bwsmtp_smtp_options">
						<th>SMTP Password</th>
						<td>
							<input type='password' name='bwsmtp_smtp_password' value='<?php echo esc_attr($options['smtp_settings']['password']); ?>' /><br />
							<span class="bwsmtp_info">The password to login to your mail server</span>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" id="settings-form-submit" class="button-primary" value="Save Changes" />
					<input type="hidden" name="bwsmtp_form_submit" value="submit" />
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'bwsmtp_nonce_name' ); ?>
				</p>
			</form>

			<div class="updated" <?php if( empty( $result ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $result; ?></strong></p>
			</div>
			<h3>Testing And Debugging Settings</h3>
			<form id="bwsmtp_settings_form" method="post" action="">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">To:</th>
						<td>
							<input type="text" name="bwsmtp_to" value=""/><br />
							<span class="bwsmtp_info">Enter the email address to recipient</span>
					</td>
					</tr>
					<tr valign="top">
						<th scope="row">Subject:</th>
						<td>
							<input type="text" name="bwsmtp_subject" value=""/><br />
							<span  class="bwsmtp_info">Enter a subject for your message</span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Message:</th>
						<td>
							<textarea name="bwsmtp_message" id="bwsmtp_message" rows="5"></textarea><br />
							<span  class="bwsmtp_info">Write your message</span>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" id="settings-form-submit" class="button" value="Send Test Email" />
					<input type="hidden" name="bwsmtp_test_submit" value="submit" />
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'bwsmtp_nonce_name' ); ?>
				</p>
			</form>
		</div><!--  #bwsmtp-mail .bwsmtp-mail -->
	<?php }
}

new BW_SMTP;
