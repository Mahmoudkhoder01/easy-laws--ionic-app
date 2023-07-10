<?php
class BWD_Users_Bootstrap{
	public function __construct(){
		// DISABLE Manage in Customizer in NAV MENUS
		add_action( 'admin_init', array( $this, 'init' ), 10 );
		// profile additions
		add_action('profile_personal_options', array( $this, 'profile_personal_options' ), 0 );
		add_action('admin_footer-profile.php', array( $this, 'remove_profile_fields'));
		add_action('admin_footer-user-edit.php', array( $this, 'remove_profile_fields'));
		// LOGIN DATES
		add_action( 'wp_login', array($this, 'wp_login_time'), 10, 2);
		add_filter( 'manage_users_columns', array($this, 'manage_users_columns'));
		add_filter( 'manage_users_custom_column',  array($this, 'manage_users_custom_column'), 10, 3);
	}

	public function init() {
		global $pagenow;
		if($pagenow != 'nav-menus.php') return;
		add_filter( 'map_meta_cap', array( $this, 'remove_customize_cap'), 10, 4 );
	}

	public function remove_customize_cap( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
		if ($cap == 'customize') {
			return array('nope'); // thanks @ScreenfeedFr, http://bit.ly/1KbIdPg
		}
		return $caps;
	}

	public function profile_personal_options(){
		?>
			<div style="text-align: right;">
				<a href="javascript:;" class="button" id="bw-profile-expand">Expand All</a>
				<a href="javascript:;" class="button" id="bw-profile-collapse">Collapse All</a>
			</div>
			<script type="text/javascript">
				jQuery('#bw-profile-expand').on('click', function(){
					jQuery('h3').addClass('active');
					jQuery('.form-table').slideDown('fast');
				});
				jQuery('#bw-profile-collapse').on('click', function(){
					jQuery('h3').removeClass('active');
					jQuery('.form-table').slideUp('fast');
				});
			</script>
		<?php
	}

	public function remove_profile_fields(){
		?>
		<style>
			table.form-table{background: #fff; padding: 10px 20px; border-collapse: initial; margin-top: -16px;}
			h3, h2{cursor: pointer; padding-bottom: 10px; border-bottom: 2px solid #e3e8eb;}
			h3.active, h2.active{border-color: #3bc8f5;}
		</style>
		<script type="text/javascript">
		// jQuery(document).ready(function($){
            jQuery("h3:contains('Personal Options')").next('.form-table').remove();
            jQuery("h3:contains('Personal Options')").remove();
            jQuery("th:contains('Biographical Info')").parents('tr').remove();
            jQuery("h3:contains('About Yourself')").html('Password');
            jQuery("h3:contains('About the user')").html('Password');
            // jQuery('.form-table').hide();
            jQuery('h3').addClass('active');
            jQuery('h3').on('click', function(){
            	var e = jQuery(this),
            		t = e.next('.form-table');

            	t.slideToggle('fast');
            	e.toggleClass('active');
            });
            jQuery("h2:contains('Personal Options')").next('.form-table').remove();
            jQuery("h2:contains('Personal Options')").remove();
            jQuery("th:contains('Biographical Info')").parents('tr').remove();
            jQuery("h2:contains('About Yourself')").html('Password');
            jQuery("h2:contains('About the user')").html('Password');
            // jQuery('.form-table').hide();
            jQuery('h2').addClass('active');
            jQuery('h2').on('click', function(){
            	var e = jQuery(this),
            		t = e.next('.form-table');

            	t.slideToggle('fast');
            	e.toggleClass('active');
            });
        // });
        </script>
        <?php
	}

	public function wp_login_time( $user_login, $user ){
		update_user_meta( $user->ID, 'last_login', current_time('mysql') );
	}

	public function manage_users_columns( $columns ){
	    $columns['user_registered'] = __('Date Registered', BW_TD);
	    $columns['last_login'] = __('Last login', BW_TD);
	    return $columns;
	}

	public function manage_users_custom_column( $value, $column_name, $user_id ){
		$user = get_userdata( $user_id );
		if ( 'user_registered' == $column_name)
			$value = date("g:i a d-M-y", strtotime( $user->user_registered));

		if ( 'last_login' == $column_name && $user->last_login)
			$value = date("g:i a d-M-y", strtotime( $user->last_login));

		return $value;
	}
}

$GLOBALS['BWD_Users_Bootstrap'] = new BWD_Users_Bootstrap;
