<?php

require_once( explode( 'wp-content', __FILE__ )[0] . 'wp-load.php' );

$o = '';
$key = isset($_GET['key']) ? trim($_GET['key']) : '';
if($key){
	$t = DB()->prefix.'app_users';
	$check = DB()->get_row("SELECT * FROM {$t} WHERE `key`='{$key}'");
	if($check){
		DB()->update($t, ['status' => 1], ['ID' => $check->ID]);
		$o = "
			<p>Thank you {$check->name} for activating your account</p>
			<p>You may now login into EasyLaws App.</p>
		";
	} else {
		$o = "
			<p>We could't extract the account for the provided key</p>
			<p>Kindly check your email and try again.</p>
		";
	}
} else {
	$o = "
		<p>No Key supplied. Halting</p>
	";
}

wp_die($o.'<p><i>EasyLaws Team.</i></p>', 'EasyLaws Account Activation');
