<?php
if (!function_exists('redux_init')):
    function redux_init() {

        $args = array();
        $args['dev_mode'] = false;
        $args['opt_name'] = 'BWMM';
        $args['admin_bar'] = false;
        $args['system_info'] = false;
        $args['display_name'] = 'Maintenance Options';
        $args['display_version'] = '';
        $args['google_api_key'] = 'AIzaSyAX_2L_UzCDPEnAHTG7zhESRVpMPS4ssII';
        $args['menu_type'] = 'menu';
        $args['page_parent'] = 'themes.php';
        $args['menu_icon'] = 'dashicons-smiley';
        $args['menu_title'] = 'Maintenance';
        $args['page_title'] = 'Maintenance Options';
        $args['page_slug'] = 'BWMM';
        $args['default_show'] = true;
        $args['default_mark'] = '*';
        $args['page_position'] = 59;
        $args['allow_sub_menu'] = false;
        $args['footer_text'] = '&nbsp;';
        $args['footer_credit'] = '&nbsp;';
        $args['show_import_export'] = false;

        $sections = array();

        $sections[] = array(
            'type' => 'divide',
        );

        $sections[] = array(
            'title' => 'General' ,
            'icon' => 'el-icon-cogs',
            'customizer' => 'true',
            'fields' => array(
                array(
                    'id' => 'favicon',
                    'type' => 'media',
                    'url' => true,
                    'title' => 'Favicon' ,
                    'subtitle' => '32x32 px PNG format',
                    'compiler' => 'true',
                ) ,
                array(
                    'id' => 'logo',
                    'type' => 'media',
                    'url' => true,
                    'title' => 'Logo' ,
                    'subtitle' => 'Logo PNG format',
                    'compiler' => 'true',
                ) ,
                array(
                    'id' => 'tracking_code',
                    'type' => 'ace_editor',
                    'mode' => 'html',
                    'theme' => 'monokai',
                    'title' => 'Tracking Code' ,
                    'subtitle' => 'Paste your Google Analytics (or other) tracking code here. This will be added into the footer template of your theme.' ,

                    //'validate' => 'js',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'css_code',
                    'type' => 'ace_editor',
                    'title' => 'CSS Code' ,
                    'subtitle' => 'Paste your CSS code here.' ,
                    'mode' => 'css',
                    'theme' => 'monokai',
                    'desc' => '',
                    'default' => ""
                ) ,
            ) ,
        );

        $sections[] = array(
            'title' => 'Content' ,
            'icon' => 'el-icon-website',

            'fields' => array(
                array(
                    'id' => 'title',
                    'type' => 'text',
                    'title' => 'Title (HTML tag)' ,
                    'default' => 'Maintenance mode'
                ) ,
                array(
                    'id' => 'heading',
                    'type' => 'text',
                    'title' => 'Heading' ,
                    'default' => 'Maintenance mode'
                ) ,
                array(
                    'id' => 'heading_color',
                    'type' => 'color',
                    'title' => 'Heading Color' ,
                    'default' => ''
                ) ,
                array(
                    'id' => 'text',
                    'type' => 'editor',
                    'title' => 'Text' ,
                    'default' => '<p>Our website is currently undergoing scheduled maintenance.<br />Thank you for your understanding.</p>'
                ) ,
                array(
                    'id' => 'text_color',
                    'type' => 'color',
                    'title' => 'Text Color' ,
                    'default' => ''
                ) ,
                array(
                    'id' => 'bg_type',
                    'type' => 'button_set',
                    'title' => 'Background Type' ,
                    'options' => array(
                        'color' => 'Color',
                        'image' => 'Image',
                    ),
                    'default' => 'color'
                ) ,
                array(
                    'id' => 'bg_color',
                    'type' => 'color',
                    'title' => 'Background Color' ,
                    'default' => '#ffffff',
                    'required' => array('bg_type','=','color'),
                ) ,
                array(
                    'id' => 'bg_image',
                    'type' => 'media',
                    'url' => true,
                    'title' => 'Background Image' ,
                    'compiler' => 'true',
                    'required' => array('bg_type','=','image'),
                ) ,
            ) ,
        );

        $tabs = array();
        global $ReduxFramework;
        $ReduxFramework = new ReduxFramework($sections, $args, $tabs);
    }
    add_action('init', 'redux_init');
endif;
