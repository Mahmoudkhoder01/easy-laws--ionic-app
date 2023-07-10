<?php

	require_once(dirname(__FILE__).'/mapping.php');

	require_once(dirname(__FILE__).'/bootstrap.php');

	require_once(dirname(__FILE__).'/admin-bar.php');
	require_once(dirname(__FILE__).'/avatar.php');
	require_once(dirname(__FILE__).'/blog.php');
	require_once(dirname(__FILE__).'/branding.php');
	require_once(dirname(__FILE__).'/cache-browser.php');
	require_once(dirname(__FILE__).'/cache-no-htaccess.php');
	require_once(dirname(__FILE__).'/cache.php');
	require_once(dirname(__FILE__).'/custom-code.php');
	require_once(dirname(__FILE__).'/db-repair.php');
	require_once(dirname(__FILE__).'/email-encoder.php');
	require_once(dirname(__FILE__).'/fix-ssl.php');
	require_once(dirname(__FILE__).'/htaccess.php');
	require_once(dirname(__FILE__).'/opcache.php');
	require_once(dirname(__FILE__).'/menu-manager.php');
	require_once(dirname(__FILE__).'/minify.php');
	require_once(dirname(__FILE__).'/smtp.php');
	require_once(dirname(__FILE__).'/spam-stopper.php');
	require_once(dirname(__FILE__).'/transients.php');
	require_once(dirname(__FILE__).'/updates.php');
	require_once(dirname(__FILE__).'/users.php');

	if(bwd_get_option('cdn'))
		require_once(dirname(__FILE__).'/cdn.php');
