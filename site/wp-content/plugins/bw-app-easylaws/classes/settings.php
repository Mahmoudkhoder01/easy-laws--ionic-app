<?php

add_action('init', function(){

        $args = [
            'opt_name' => 'APP',
            'page_slug' => 'app_settings',
            'menu_type' => 'menu',
            'allow_sub_menu' => false,
            'page_parent' => '', //'themes.php';
            'menu_title' => 'Settings',
            'page_title' => 'App Settings',
            'page_position' => '99.001',
            'show_import_export' => false,
        ];

        $args['page_permissions'] = 'manage_options'; //'manage_options';

        $sections = array();

        $sections[] = [
            'title' => 'Settings' ,
            'icon' => 'fa fa-cogs',
            'fields' => [[
                'id'       => 'forcessl',
                'type'     => 'switch',
                'title'    => 'Force SSL',
                'default'  => false,
                'desc' => '',
            ],[
                'id'       => 'default_subject_image',
                'type'     => 'media',
                'title'    => 'Default Subject Image',
                'default'  => '',
                // 'url'      => true,
                'compiler' => 'true',
                'subtitle' => 'Fallback image to subjects with no image supplied',
                'desc'     => '500x240 jpg image',
            ],[
                'id'       => 'default_subject_color',
                'type'     => 'color',
                'title'    => 'Default Subject Color',
                'default'  => '#000',
                'subtitle' => 'Fallback subject color',
                'desc'     => '',
                'transparent' => false,
            ],[
                'id'       => 'allow_requests',
                'type'     => 'switch',
                'title'    => 'Allow Voice Requests',
                'default'  => true,
                'desc' => 'Allow / Disallow Voice requests from mobile app',
            ]]
        ];

        $sections[] = [
            'title' => 'OneSignal' ,
            'icon' => 'fa fa-bell-o',

            'fields' => [[
                'id' => 'onesignal_app_id',
                'type' => 'text',
                'title' => 'App ID' ,
                'desc' => 'Your 36 character alphanumeric app ID. You can find this on Setup > OneSignal Keys > Step 2.',
                'default' => '',
            ],[
                'id' => 'onesignal_rest_api_key',
                'type' => 'text',
                'title' => 'REST API Key' ,
                'desc' => 'Your 48 character alphanumeric REST API Key. You can find this on Setup > OneSignal Keys > Step 2.',
                'default' => '',
            ]]
        ];

        $sections[] = [
            'title' => 'Styles / Scripts' ,
            'icon' => 'fa fa-code',
            'fields' => [[
                'id' => 'css_header',
                'type' => 'ace_editor',
                'mode'     => 'html', // css
                'theme'    => 'monokai',
                'title' => 'CSS' ,
                'subtitle' => '&lt;style&gt;...&lt;/style&gt;',
                'desc' => 'The "Styles" will be included in the «head» element.',
            ],[
                'id' => 'js_header',
                'type' => 'ace_editor',
                'mode'     => 'html',
                'theme'    => 'monokai',
                'title' => 'Scripts (for the head element)' ,
                'subtitle' => '&lt;script type="text/javascript"&gt;...&lt;/script&gt;',
                'desc' => 'The "Scripts (in head)" will be included in the «head» element.',
            ], [
                'id' => 'js_footer',
                'type' => 'ace_editor',
                'mode'     => 'html', // javascript, html, css
                'theme'    => 'monokai',
                'title' => 'Scripts (end of the body tag)' ,
                'subtitle' => '&lt;script type="text/javascript"&gt;...&lt;/script&gt;',
                'desc' => 'The "Scripts" will be included at the bottom of the «body» element.',
            ]]
        ];

        $cron_url = site_url('/cron/worker/?').get_option('APP')['cron_secret'];
        $cron_daily_url = site_url('/cron/daily/?').get_option('APP')['cron_secret'];

        $sections[] = [
            'title' => 'Cron' ,
            'icon' => 'fa fa-sun-o',

            'fields' => [[
                'id' => 'cron_secret',
                'type' => 'text',
                'title' => 'Cron Settings' ,
                'desc' => 'a secret hash which is required to execute the cron',
                'default' => md5(uniqid()),
            ], [
                'id' => 'cron_desc',
                'type' => 'raw',
                'title' => '',
                'desc' => '
                    You can keep a browser window open with following URL<br>
                    <a href="'.$cron_url.'" target="_blank"><code>'.$cron_url.'</code></a><br><br>
                    call it directly<br>
                    <code>curl --silent '.$cron_url.'</code><br><br>
                    or set up a cron<br>
                    <code>*/5 * * * * wget -q -O - "'.$cron_url.'" > /dev/null 2>&1</code><br><br>

                    <h3>Daily Cron</h3>
                    <code>0 3 * * * wget -q -O - "'.$cron_daily_url.'" > /dev/null 2>&1</code><br>
                    <i>Run every day at 3:00 AM</i>
                ',
            ]]
        ];

        $tabs = array();
        global $ReduxFramework;
        $ReduxFramework = new ReduxFramework($sections, $args, $tabs);
});
