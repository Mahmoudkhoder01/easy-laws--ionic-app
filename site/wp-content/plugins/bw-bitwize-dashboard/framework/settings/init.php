<?php

if (!function_exists('bwd_sys_settings')):
    function bwd_sys_settings() {

        if(get_option('bw_cache_status', 0) == 1){
            $cache_status = '<span style="color:green;">Enabled</span> - <a href="'.add_query_arg('bw_cache','deactivate').'">Deactivate</a>';
        } else {
            $cache_status = '<span style="color:red;">Disabled</span> - <a href="'.add_query_arg('bw_cache','activate').'">Activate</a>';
        }

        if(get_option('bw_cache_nohtaccess_status', 0) == 1){
            $cache_status = '---';
            $cache_nohtaccess_status = '<span style="color:green;">Enabled</span> - <a href="'.add_query_arg('bw_cache','deactivate_nohtaccess').'">Deactivate</a>';
        } else {
            $cache_nohtaccess_status = '<span style="color:red;">Disabled</span> - <a href="'.add_query_arg('bw_cache','activate_nohtaccess').'">Activate</a>';
        }

        if(get_option('bw_cache_status', 0) == 1){
            $cache_nohtaccess_status = '---';
        }

        if(get_option('bw_browser_cache_status', 0) == 1){
            $browser_cache_status = '<span style="color:green;">Enabled</span> - <a href="'.add_query_arg('bw_cache','deactivate_browser').'">Deactivate</a>';
        } else {
            $browser_cache_status = '<span style="color:red;">Disabled</span> - <a href="'.add_query_arg('bw_cache','activate_browser').'">Activate</a>';
        }

        // START REDUX

        $args = array();
        $args['opt_name'] = 'BWDO';
        $args['page_slug'] = 'BWDO';
        $args['display_name'] = 'System Setting';
        $args['page_title'] = 'System Setting';
        $args['menu_title'] = 'System Setting';

        $args['open_expanded'] = false;

        $args['dev_mode'] = false;
        $args['admin_bar'] = false;
        $args['system_info'] = false;
        $args['show_import_export'] = false;
        $args['display_version'] = '';
        $args['google_api_key'] = 'AIzaSyAX_2L_UzCDPEnAHTG7zhESRVpMPS4ssII';
        $args['menu_type'] = 'submenu';
        $args['page_parent'] = NULL;
        $args['menu_icon'] = 'dashicons-smiley';
        $args['default_show'] = false;
        $args['default_mark'] = '*';
        $args['page_position'] = 59;
        $args['allow_sub_menu'] = false;
        $args['footer_text'] = '&nbsp;';
        $args['footer_credit'] = '&nbsp;';

        $sections = array();

        $sections[] = array(
            'title' => 'Dashboard Settings' ,
            'icon' => 'fa fa-cog',

            'fields' => array(
                array(
                    'id' => 'use_mappings',
                    'type' => 'switch',
                    'title' => 'Disable Domain Mapping?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'allow_wp',
                    'type' => 'switch',
                    'title' => 'Allow WP Requests?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'coreupdates',
                    'type' => 'switch',
                    'title' => 'Enable CORE update?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'allupdates',
                    'type' => 'switch',
                    'title' => 'Disable Extensions/Themes Updates?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'cronjobs',
                    'type' => 'switch',
                    'title' => 'Enable CRON Jobs?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'cron-manager',
                    'type' => 'switch',
                    'title' => 'Enable Cron Manager?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'xmlrpc',
                    'type' => 'switch',
                    'title' => 'Enable this to allow XMLRPC' ,
                    'subtitle' => 'highly recommended to leave XMLRPC disabled',
                    'desc' => '',
                ) ,

                // array(
                //     'id' => 'divider_1',
                //     'type' => 'divide',
                //     'desc' => '',
                // ) ,


                array(
                    'id' => 'force_secure_dashboard',
                    'type' => 'switch',
                    'title' => 'Force Secure dashboard?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'vc_frontend',
                    'type' => 'switch',
                    'title' => 'Activate Frontend Editing?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'admin_widgets',
                    'type' => 'switch',
                    'title' => 'Enable Widgets for admins?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'show_customize',
                    'type' => 'switch',
                    'title' => 'Show Customizer for admins?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'jump_menu',
                    'type' => 'switch',
                    'title' => 'Disable Jump Menu?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,

            ) ,
        );

        $sections[] = array(
            'title' => 'Blog/Shop' ,
            'icon' => 'fa fa-rss',

            'fields' => array(
                // array(
                //     'id' => 'woo_session',
                //     'type' => 'switch',
                //     'title' => 'Enable Woo Session Handler?' ,
                //     'subtitle' => 'Enable this to use sessions in separate DB tables, better for heavy traffic sites',
                //     'desc' => '',
                // ) ,
                array(
                    'id' => 'disable_blog',
                    'type' => 'switch',
                    'title' => 'Disable Blog?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'disable_comments',
                    'type' => 'switch',
                    'title' => 'Disable Comments?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'disable_reviews',
                    'type' => 'switch',
                    'title' => 'Disable Product Reviews?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
            ) ,
        );

        $sections[] = array(
            'title' => 'Developer' ,
            'icon' => 'fa fa-code',

            'fields' => array(
                array(
                    'id' => 'metabox',
                    'type' => 'switch',
                    'title' => 'Enable Meta Box?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'enable_rest',
                    'type' => 'switch',
                    'title' => 'Enable REST API?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'bwquerymon',
                    'type' => 'switch',
                    'title' => 'Disable Query Monitor?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'bwqm_fe',
                    'type' => 'switch',
                    'title' => 'Enable Query Monitor (FRONT END)?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'block_bots',
                    'type' => 'switch',
                    'title' => 'Disable BOT & Referrals Blocking?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,

            ) ,
        );

        $sections[] = array(
            'title' => 'Extensions' ,
            'icon' => 'fa fa-th',

            'fields' => array(
                array(
                    'id' => 'cdn',
                    'type' => 'switch',
                    'title' => 'Enable CDN?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'googleauth',
                    'type' => 'switch',
                    'title' => 'Disable Google Authenticator?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'ddfi',
                    'type' => 'switch',
                    'title' => 'Disable Drag-Drop Featured Image?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'frt',
                    'type' => 'switch',
                    'title' => 'Enable Force Regenerate Thumbs?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'media-folder',
                    'type' => 'switch',
                    'title' => 'Enable Media Folders?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'weather',
                    'type' => 'switch',
                    'title' => 'Enable Weather?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,

            ) ,
        );

        $sections[] = array(
            'title' => 'Geeky' ,
            'icon' => 'fa fa-bug',

            'fields' => array(
                array(
                    'id' => 'bitwize',
                    'type' => 'switch',
                    'title' => 'Enable Bitwize Branding?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
                array(
                    'id' => 'sysdash',
                    'type' => 'switch',
                    'title' => 'Enable System Dashboard?' ,
                    'subtitle' => '',
                    'desc' => '',
                ) ,
            ) ,
        );

        $sections[] = array(
            'title' => 'Actions' ,
            'icon' => 'fa fa-cogs',

            'fields' => array(
                array(
                    'id' => 'bwtrans',
                    'type' => 'info',
                    'raw_html' => true,
                    'title' => 'Transients',
                    'desc' => '
                        <p><a href="#" id="trans_count">Get Count</a> <span id="trans_display"></span></p>
                        '.(class_exists('WooCommerce') ? '<a href="'.add_query_arg('bw_transients','clear_woo').'">Clear Shop Transients</a> - ' : '' ).'
                        <a href="'.add_query_arg('bw_transients','clear_expired').'">Clear Expired</a> -
                        <a href="'.add_query_arg('bw_transients','clear_all').'">Clear All</a>
                    ',
                ) ,
                array(
                    'id' => 'bwbc_act',
                    'type' => 'info',
                    'raw_html' => true,
                    'title' => 'Browser Cache',
                    'desc' => '<p>'.$browser_cache_status.'</p>',
                ) ,
                array(
                    'id' => 'bwcache',
                    'type' => 'info',
                    'raw_html' => true,
                    'title' => 'Cache',
                    'desc' => '<p>'.$cache_status.'</p>',
                ) ,
                array(
                    'id' => 'bwcache_nohtaccess',
                    'type' => 'info',
                    'raw_html' => true,
                    'title' => 'NO HTAccess Cache',
                    'desc' => '<p>'.$cache_nohtaccess_status.'</p>',
                ) ,
                array(
                    'id' => 'repair_db',
                    'type' => 'info',
                    'raw_html' => true,
                    'title' => 'Repair DB',
                    'desc' => '<p><a class="bwd_repair_db" href="#">Repair</a> - <a class="bwd_repair_db optimize" href="#">Repair & Optimize</a></p><div id="bwd_repair_display"></div>',
                ) ,
                array(
                    'id' => 'backup_db',
                    'type' => 'info',
                    'raw_html' => true,
                    'title' => 'Backup DB',
                    'desc' => '<p><a class="bwd_backup_db" href="#">Backup</a></p><div id="bwd_backup_display"></div>',
                ) ,
            ) ,
        );

        global $ReduxFramework;
        $ReduxFramework = new ReduxFramework($sections, $args);
    }
    add_action('init', 'bwd_sys_settings');
endif;
