<?php

class App_Database_Setup
{
	public $version_option = 'app_database_version';

	public function __construct(){
		add_action('admin_init', array($this, 'init'));
	}

	public function init(){
		if( ( false ===( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) ) ) {
            $this->setup();
        }
	}

	 public function setup($force_setup = false){
		$current_version = get_option( $this->version_option );
        $has_version_changed = $current_version != APP_DB_VERSION;
        if( $has_version_changed || $force_setup ) {
        	$this->setup_databases();
            update_option( $this->version_option, APP_DB_VERSION );
        }
    }

    public function setup_databases(){
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        if( !empty( DB()->charset ) ) $charset_collate = "DEFAULT CHARACTER SET ".DB()->charset;
        if( !empty( DB()->collate ) ) $charset_collate.= " COLLATE ".DB()->collate;
        // $charset_collate = "DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
        $max_index_length = 191;
        $APP_PREFIX = DB()->prefix.'app_';

        // SPONSORS.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}sponsors (
            `ID` bigint(20) unsigned NOT NULL auto_increment,
            `user_id` bigint(20) unsigned NOT NULL default '0',
            `author` bigint(20) unsigned NOT NULL default '0',
            `name` varchar(250) NOT NULL default '',
            `contact_name` varchar(250) NOT NULL default '',
            `email` varchar(250) NOT NULL default '',
            `phone` varchar(250) NOT NULL default '',
            `address` TEXT NULL,
            `country` varchar(250) NOT NULL default '',
            `date_created` int(11) NOT NULL DEFAULT '0',
            `date_edited` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY  (`ID`),
            KEY `user_id` (`user_id`),
            KEY `author` (`author`)
        ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );
        
        // SPONSOR ADS.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}sponsor_ads (
            `ID` bigint(20) unsigned NOT NULL auto_increment,
            `sponsor_id` bigint(20) unsigned NOT NULL default '0',
            `title` varchar(250) NOT NULL default '',
            `link` varchar(250) NOT NULL default '',
            `image` varchar(250) NOT NULL default '',
            `type` int(11) NOT NULL DEFAULT '0',
            `impressions` int(11) NOT NULL DEFAULT '0',
            `clicks` int(11) NOT NULL DEFAULT '0',
            `start` int(11) NOT NULL DEFAULT '0',
            `end` int(11) NOT NULL DEFAULT '0',
            `sections` varchar(250) NOT NULL default '',
            `questions` varchar(250) NOT NULL default '',
            `subjects` varchar(250) NOT NULL default '',
            `screens` varchar(250) NOT NULL default '',
            `active` int(11) NOT NULL DEFAULT '0',
            `date_created` int(11) NOT NULL DEFAULT '0',
            `date_edited` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY  (`ID`),
            KEY `sponsor_id` (`sponsor_id`),
            KEY `start` (`start`),
            KEY `end` (`end`)
        ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // SUBJECT NOTES.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}subject_notes (
            `ID` bigint(20) unsigned NOT NULL auto_increment,
            `subject_id` bigint(20) unsigned NOT NULL default '0',
            `note` TEXT NULL,
            `date_created` int(11) NOT NULL DEFAULT '0',
            `date_edited` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY  (`ID`),
            KEY `user_id` (`subject_id`)
        ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // NOTIFICATION.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}notifications (
            `ID` bigint(20) unsigned NOT NULL auto_increment,
            `user_id` bigint(20) unsigned NOT NULL default '0',
            `title` varchar(250) NOT NULL default '',
            `details` TEXT NULL,
            `action` varchar(250) NOT NULL default '',
            `action_id` varchar(250) NOT NULL default '',
            `is_read` tinyint(1) unsigned NOT NULL default '0',
            `date_created` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY  (`ID`),
            KEY `user_id` (`user_id`),
            KEY `is_read` (`is_read`)
        ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // QUESTIONS.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}questions (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `author` bigint(20) unsigned NOT NULL default '0',
                `status` tinyint(1) unsigned NOT NULL default '1',
                `status_en` tinyint(1) unsigned NOT NULL default '0',
                `status_fr` tinyint(1) unsigned NOT NULL default '0',
                `title` varchar(250) NOT NULL default '',
                `title_en` varchar(250) NOT NULL default '',
                `title_fr` varchar(250) NOT NULL default '',
                `categories` varchar(250) NOT NULL default '',
                `details` TEXT NULL,
                `details_en` TEXT NULL,
                `details_fr` TEXT NULL,
                `did_you_know` TEXT NULL,
                `did_you_know_en` TEXT NULL,
                `did_you_know_fr` TEXT NULL,
                `examples` TEXT NULL,
                `examples_en` TEXT NULL,
                `examples_fr` TEXT NULL,
                `notes` TEXT NULL,
                `notes_en` TEXT NULL,
                `notes_fr` TEXT NULL,
                `videos` varchar(250) NULL,
                `images` varchar(250) NULL,
                `links` TEXT NULL,
                `references` TEXT NULL,
                `tags` TEXT NULL,
                `keywords` TEXT NULL,
                `votes_up` int(11) null default '0',
                `votes_down` int(11) null default '0',
                `likes` int(11) null default '0',
                `comments` int(11) null default '0',
                `views` bigint(20) null default '0',
                `menu_order` int(11) NOT NULL DEFAULT '0',
                `trashed` int(11) NOT NULL DEFAULT '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `status` (`status`),
                KEY `status_en` (`status_en`),
                KEY `status_fr` (`status_fr`),
                KEY `votes_up` (`votes_up`),
                KEY `votes_down` (`votes_down`),
                KEY `likes` (`likes`),
                KEY `comments` (`comments`),
                KEY `views` (`views`),
                KEY `author` (`author`),
                FULLTEXT `ft_search` (`title`, `details`, `keywords_translated`, `title_en`, `title_fr`, `details_en`, `details_fr`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // QUESTION LOGS.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}question_logs (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `question_id` bigint(20) unsigned NOT NULL default '0',
                `details` TEXT NULL,
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `question_id` (`question_id`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // QUESTION COMMENTS.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}question_comments (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `question_id` bigint(20) unsigned NOT NULL default '0',
                `reply_to` bigint(20) unsigned NOT NULL default '0',
                `status` tinyint(1) unsigned NOT NULL default '0',
                `details` TEXT NULL,
                `votes_up` int(11) null default '0',
                `votes_down` int(11) null default '0',
                `likes` int(11) null default '0',
                `comments` int(11) null default '0',
                `menu_order` int(11) NOT NULL DEFAULT '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                `date_approved` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`),
                KEY `question_id` (`question_id`),
                KEY `reply_to` (`reply_to`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // QUESTIONS Cats.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}subjects (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `author` bigint(20) unsigned NOT NULL default '0',
                `status` tinyint(1) unsigned NOT NULL default '1',
                `title` varchar(250) NOT NULL default '',
                `parent` bigint(20) unsigned NOT NULL default '0',
                `details` TEXT NULL,
                `title_en` varchar(250) NOT NULL default '',
                `title_fr` varchar(250) NOT NULL default '',
                `details_en` TEXT NULL,
                `details_fr` TEXT NULL,
                `image` varchar(255) NULL,
                `keywords` TEXT NULL,
                `likes` BIGINT(20) NOT NULL DEFAULT '0',
                `views` bigint(20) null default '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `status` (`status`),
                KEY `author` (`author`),
                KEY `views` (`views`),
                KEY `likes` (`likes`),
                KEY `parent` (`parent`),
                FULLTEXT `ft_search` (`title`, `details`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // Keywords.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}keywords (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `author` bigint(20) unsigned NOT NULL default '0',
                `status` tinyint(1) unsigned NOT NULL default '1',
                `title` varchar(250) NOT NULL default '',
                `details` TEXT NULL,
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `status` (`status`),
                KEY `author` (`author`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // tags.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}tags (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `author` bigint(20) unsigned NOT NULL default '0',
                `status` tinyint(1) unsigned NOT NULL default '1',
                `title` varchar(250) NOT NULL default '',
                `details` TEXT NULL,
                `title_en` varchar(250) NOT NULL default '',
                `title_fr` varchar(250) NOT NULL default '',
                `details_en` TEXT NULL,
                `details_fr` TEXT NULL,
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `status` (`status`),
                KEY `author` (`author`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // definitions.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}definitions (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `author` bigint(20) unsigned NOT NULL default '0',
                `status` tinyint(1) unsigned NOT NULL default '1',
                `title` TEXT NOT NULL default '',
                `details` TEXT NULL,
                `examples` TEXT NULL,
                `examples_en` TEXT NULL,
                `examples_fr` TEXT NULL,
                `notes` TEXT NULL,
                `notes_en` TEXT NULL,
                `notes_fr` TEXT NULL,
                `tags` TEXT NULL,
                `keywords` TEXT NULL,
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `status` (`status`),
                KEY `author` (`author`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // references.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}references (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `author` bigint(20) unsigned NOT NULL default '0',
                `status` tinyint(1) unsigned NOT NULL default '1',
                `parent` bigint(20) unsigned NOT NULL default '0',
                `title` varchar(250) NOT NULL default '',
                `details` TEXT NULL,
                `title_en` varchar(250) NOT NULL default '',
                `title_fr` varchar(250) NOT NULL default '',
                `details_en` TEXT NULL,
                `details_fr` TEXT NULL,
                `keywords` TEXT NULL,
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `status` (`status`),
                KEY `author` (`author`),
                KEY `parent` (`parent`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // users.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}users (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `name` varchar(250) NOT NULL default '',
                `image` varchar(250) NOT NULL default '',
                `email` varchar(250) NOT NULL default '',
                `password` varchar(250) NOT NULL default '',
                `phone` varchar(250) NOT NULL default '',
                `dob` DATE NULL;
                `gender` varchar(10) NOT NULL default 'male',
                `type` tinyint(1) unsigned NOT NULL default '0',
                `status` tinyint(1) unsigned NOT NULL default '0',
                `key` varchar(250) NOT NULL default '',
                `provider` varchar(250) NOT NULL default 'native',
                `fb_id` varchar(250) NOT NULL default '',
                `google_id` varchar(250) NOT NULL default '',
                `is_admin` TINYINT(1) NOT NULL DEFAULT '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                `last_login` int(11) NOT NULL DEFAULT '0',
                `date_active` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `status` (`status`),
                KEY `is_admin` (`is_admin`),
                KEY `type` (`type`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // user likes.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}user_likes (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `question_id` bigint(20) unsigned NOT NULL default '0',
                `subject_id` bigint(20) unsigned NOT NULL default '0',
                `type` INT(1) NOT NULL DEFAULT '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`),
                KEY `question_id` (`question_id`),
                KEY `subject_id` (`subject_id`),
                KEY `type` (`type`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // user votes.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}user_votes (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `question_id` bigint(20) unsigned NOT NULL default '0',
                `direction` tinyint(1) unsigned NOT NULL default '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`),
                KEY `direction` (`direction`),
                KEY `question_id` (`question_id`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // user comment votes.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}user_comment_votes (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `comment_id` bigint(20) unsigned NOT NULL default '0',
                `direction` tinyint(1) unsigned NOT NULL default '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`),
                KEY `direction` (`direction`),
                KEY `comment_id` (`comment_id`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // user browsing history.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}user_browsing_history (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `question_id` bigint(20) unsigned NOT NULL default '0',
                `title` varchar(250) NOT NULL default '',
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`),
                KEY `question_id` (`question_id`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // user search history.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}user_search_history (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `keyword` varchar(250) NOT NULL default '',
                `dat` DATE NULL,
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // search log.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}search_log (
            `ID` bigint(20) unsigned NOT NULL auto_increment,
            `keyword` varchar(250) NOT NULL default '',
            `month` varchar(250) NOT NULL default '',
            `has_results` int(11) NOT NULL DEFAULT '0',
            `count` bigint(20) unsigned NOT NULL default '1',
            PRIMARY KEY  (`ID`),
            UNIQUE `keyword` (`keyword`, `month`, `has_results`) USING BTREE
        ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // search dump.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}search_dump (
            `ID` bigint(20) unsigned NOT NULL auto_increment,
            `user_id` bigint(20) unsigned NOT NULL default '0',
            `keyword` varchar(250) NOT NULL default '',
            `date_created` int(11) NOT NULL DEFAULT '0',
            `has_results` int(11) NOT NULL DEFAULT '0',
            `count` bigint(20) unsigned NOT NULL default '0',
            PRIMARY KEY  (`ID`)
        ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // user used subjects.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}user_used_subjects (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `subject_id` bigint(20) unsigned NOT NULL default '0',
                `cnt` bigint(20) unsigned NOT NULL default '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`),
                KEY `subject_id` (`subject_id`),
                KEY `cnt` (`cnt`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // user dashboards.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}user_dashboards (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `dashboard_id` bigint(20) unsigned NOT NULL default '0',
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`),
                KEY `dashboard_id` (`dashboard_id`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // dashboards.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}dashboards (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `author` bigint(20) unsigned NOT NULL default '0',
                `title` varchar(250) NOT NULL default '',
                `subjects` varchar(250) NOT NULL default '',
                `details` TEXT NULL,
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `author` (`author`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // requests.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}requests (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `user_id` bigint(20) unsigned NOT NULL default '0',
                `details` text null,
                `file` varchar(250) NOT NULL default '',
                `date_created` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY `user_id` (`user_id`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );

        // Devices.
        $sql = "CREATE TABLE IF NOT EXISTS {$APP_PREFIX}devices (
                `ID` bigint(20) unsigned NOT NULL auto_increment,
                `status` int(11) NOT NULL DEFAULT '1',
                `user_id` bigint(20) NOT NULL DEFAULT '0',
                `push_id` varchar(250) NOT NULL DEFAULT '0',
                `uuid` varchar(250) NULL,
                `model` varchar(250) NULL,
                `platform` varchar(250) NULL,
                `version` varchar(250) NULL,
                `manufacturer` varchar(250) NULL,
                `serial` varchar(250) NULL,
                `date_created` int(11) NOT NULL DEFAULT '0',
                `date_edited` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`ID`),
                KEY user_id (user_id),
                KEY status (status),
                UNIQUE(`uuid`)
            ) AUTO_INCREMENT=1000000 $charset_collate;";
        dbDelta( $sql );
	}
}

new App_Database_Setup;
