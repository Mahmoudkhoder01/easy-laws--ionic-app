<?php

// Jump Menu
if(!bwd_get_option('jump_menu'))
	require_once(dirname(__FILE__) . '/jump-menu/jump-menu.php');

if(!bwd_get_option('googleauth'))
	require_once(dirname(__FILE__).'/google-authenticator/google-authenticator.php');

if(!bwd_get_option('ddfi'))
	require_once(dirname(__FILE__).'/drag-drop-featured-image/index.php');

if(bwd_get_option('cron-manager'))
	require_once(dirname(__FILE__).'/cron-manager/index.php');

if(bwd_get_option('frt'))
	require_once(dirname(__FILE__).'/force-regenerate-thumbnails/force-regenerate-thumbnails.php');

if(bwd_get_option('media-folder'))
	require_once(dirname(__FILE__).'/media-folder/media-folder.php');

if(bwd_get_option('weather'))
	require_once(dirname(__FILE__).'/weather/weather.php');

