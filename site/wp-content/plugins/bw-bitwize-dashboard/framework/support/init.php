<?php

	/* VISUAL COMPOSER
	================================================== */
	if(bw_is_plugin_active('bw-visual-composer/bw-visual-composer.php') || bw_is_plugin_active('js_composer/js_composer.php'))
		include_once(dirname( __FILE__ ).'/vc/init.php');

	/* BWCOMMERCE
	================================================== */
	if(is_woocommerce_active())
		include_once(dirname( __FILE__ ) . '/shop/init.php');
