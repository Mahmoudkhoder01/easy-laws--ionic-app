<?php
if (!function_exists('bwd_custom_code')):
    function bwd_custom_code() {

        $args = array();
        $args['opt_name'] = 'BWDCCODE';
        $args['page_slug'] = 'BWDCCODE';
        $args['display_name'] = 'Custom Code';
        $args['page_title'] = 'Custom Code';
        $args['menu_title'] = 'Custom Code';

        $args['open_expanded'] = true;

        $args['dev_mode'] = false;
        $args['admin_bar'] = false;
        $args['system_info'] = false;
        $args['show_import_export'] = false;
        $args['display_version'] = '';
        $args['google_api_key'] = 'AIzaSyAX_2L_UzCDPEnAHTG7zhESRVpMPS4ssII';
        $args['menu_type'] = 'menu';
        $args['page_parent'] = NULL;
        $args['menu_icon'] = 'dashicons-smiley';
        $args['default_show'] = false;
        $args['default_mark'] = '*';
        $args['page_position'] = 590;
        $args['allow_sub_menu'] = false;
        $args['footer_text'] = '&nbsp;';
        $args['footer_credit'] = '&nbsp;';
        $args['page_permissions'] = 'manage_options';

        $sections = array();

        $sections[] = array(
            'title' => 'Custom Code' ,
            'icon' => 'fa fa-code',

            'fields' => array(
                array(
                    'id' => 'css_code',
                    'type' => 'ace_editor',
                    'title' => 'CSS Styles' ,
                    'subtitle' => '',
                    'mode' => 'css',
                    'theme' => 'monokai',
                    'desc' => 'The "Styles" will be included verbatim in &laquo;style&raquo; tags in the &laquo;head&raquo; element of your html.',
                    'default' => ""
                ) ,
                array(
                    'id' => 'js_code_header',
                    'type' => 'ace_editor',
                    'mode' => 'html',
                    'theme' => 'monokai',
                    'title' => 'Scripts (for the head element)',
                    'subtitle' => '&lt;script type="text/javascript"&gt;...&lt;/script&gt;',
                    'desc' => 'The "Scripts (in head)" will be included in the &laquo;head&raquo; element of your html.',
                ) ,
                array(
                    'id' => 'js_code_footer',
                    'type' => 'ace_editor',
                    'mode' => 'html',
                    'theme' => 'monokai',
                    'title' => 'Scripts (end of the body tag)',
                    'subtitle' => '&lt;script type="text/javascript"&gt;...&lt;/script&gt;',
                    'desc' => 'The "Scripts" will be included at the bottom of the &laquo;body&raquo; element of your html.',
                ) ,

            ) ,
        );

        $tabs = array();
        global $ReduxFramework;
        $ReduxFramework = new ReduxFramework($sections, $args, $tabs);
    }
    add_action('init', 'bwd_custom_code', 0);
    add_action('admin_menu', function(){
        remove_menu_page('BWDCCODE');
    }, 1000);
endif;

add_action('wp_head', function(){
    global $BWDCCODE;
    if (!empty($BWDCCODE['css_code'])) echo '<style type="text/css">'.$BWDCCODE['css_code'].'</style>';
    if (!empty($BWDCCODE['js_code_header'])) echo $BWDCCODE['js_code_header'];
}, 9991);

add_action('wp_footer', function(){
    global $BWDCCODE;
    if (!empty($BWDCCODE['js_code_footer'])) echo $BWDCCODE['js_code_footer'];
}, 9991);
