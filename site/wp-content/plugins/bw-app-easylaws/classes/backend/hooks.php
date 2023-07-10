<?php
class App_Admin_Hooks{

	public function __construct(){
		add_filter( 'manage_users_columns', [$this, 'manage_users_columns'] );
		add_filter( 'manage_users_custom_column', [$this, 'manage_users_custom_column'], 10, 3 );

		add_action( 'user_new_form', [$this, 'user_new_form'] );
		add_action( 'edit_user_created_user', [$this, 'edit_user_created_user'], 10, 2 );

		add_action( 'edit_user_profile', [$this, 'edit_user_profile'] );
		add_action( 'edit_user_profile_update', [$this, 'edit_user_profile_update'] );
	}

	function manage_users_columns($columns){
		$columns['notification'] = '<i class="fa fa-bell-o"></i>';
	    return $columns;
	}

	function manage_users_custom_column($val, $column_name, $user_id){
		if($column_name == 'notification'){
			$can_receive_notifications = trim(get_user_option('can_receive_notifications', $user_id));
			if ($can_receive_notifications == 'enabled'){
				return '<i class="fa fa-bell"></i>';
			} else {
				return '';
			}
		}
		return $val;
	}

	function user_new_form(){
		$creating = isset( $_POST['createuser'] );
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Can Receive Notifications?' ) ?></th>
				<td>
					<input type="checkbox" name="can_receive_notifications" id="can_receive_notifications" value="enabled" checked="checked" />
				</td>
			</tr>
		</table>
		<?php
	}

	function edit_user_created_user($user_id, $notify){
		$can_receive_notifications = ! empty( $_POST['can_receive_notifications'] );
		$can_receive_notifications = $can_receive_notifications ? 'enabled' : 'disabled';
		update_user_option($user_id, 'can_receive_notifications', $can_receive_notifications, true);
	}

	function edit_user_profile() {
		global $user_id;
		$can_receive_notifications = trim(get_user_option('can_receive_notifications', $user_id));
		?>
		<h3>Notifications Settings</h3>
		<table class="form-table"><tbody>
			<tr>
				<th scope="row">Can Receive Notifications?</th>
				<td>
					<input name="can_receive_notifications" id="can_receive_notifications"  class="tog" type="checkbox" <?php checked($can_receive_notifications, 'enabled'); ?> />
				</td>
			</tr>
		</tbody></table>
		<?php
	}

	function edit_user_profile_update() {
		global $user_id;
		$can_receive_notifications = ! empty( $_POST['can_receive_notifications'] );
		$can_receive_notifications = $can_receive_notifications ? 'enabled' : 'disabled';
		update_user_option($user_id, 'can_receive_notifications', $can_receive_notifications, true);
	}

}
new App_Admin_Hooks;
