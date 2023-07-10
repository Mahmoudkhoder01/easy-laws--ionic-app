<?php

require_once( explode( 'wp-content', __FILE__ )[0] . 'wp-load.php' );

$id = isset($_GET['id']) ? intval(trim($_GET['id'])) : '';

if(!$id) die('ERROR');

$t = PRX.'sponsor_ads';
$link = DB()->get_var("SELECT link FROM $t WHERE ID=$id");
DB()->query("update $t SET clicks = clicks + 1 WHERE ID=$id");
wp_redirect($link);

// echo "<h1>$id</h1>";

die();
