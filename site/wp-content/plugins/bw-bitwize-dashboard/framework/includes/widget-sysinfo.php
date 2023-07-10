<?php

class BW_Dashboard_Widget_SysInfo
{

	public function __construct(){

		add_action('wp_dashboard_setup', array($this, 'dashboard_setup'));
	}

	public function dashboard_setup(){
		wp_add_dashboard_widget('bw_sysinfo', '<i class="fa fa-laptop"></i> System Info', array($this, 'dashboard'));
		wp_add_dashboard_widget('bw_sysinfo_server', '<i class="fa fa-server"></i> Server Info', array($this, 'server_dash'));
	}

	public function dashboard(){
		echo $this->sysinfo_get();
	}

	public function server_dash(){
		echo $this->sysinfo_get_server();
	}

	function get_host() {
		$host = false;
		if ( defined( 'WPE_APIKEY' ) ) {
			$host = 'WP Engine';
		} elseif ( defined( 'PAGELYBIN' ) ) {
			$host = 'Pagely';
		} elseif ( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
			$host = 'ICDSoft';
		} elseif ( DB_HOST == 'mysqlv5' ) {
			$host = 'NetworkSolutions';
		} elseif ( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
			$host = 'iPage';
		} elseif ( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
			$host = 'IPower';
		} elseif ( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
			$host = 'MediaTemple Grid';
		} elseif ( strpos( DB_HOST, '.pair.com' ) !== false ) {
			$host = 'pair Networks';
		} elseif ( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
			$host = 'Rackspace Cloud';
		} elseif ( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
			$host = 'SysFix.eu Power Hosting';
		} elseif ( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
			$host = 'Flywheel';
		} else {
			$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
		}
		return $host;
	}

	function get_ajax_url() {
		$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';
		$current_url = $this->get_current_page_url();
		$ajax_url    = admin_url( 'admin-ajax.php', $scheme );
		if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
			$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
		}
		return apply_filters( 'bw_ajax_url', $ajax_url );
	}

	function get_current_page_url() {
		if ( is_front_page() ) :
			$page_url = home_url();
		else :
			$page_url = 'http';
			if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
				$page_url .= "s";
			}
			$page_url .= "://";
			if ( isset( $_SERVER["SERVER_PORT"] ) && $_SERVER["SERVER_PORT"] != "80" ) {
				$page_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
			} else {
				$page_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
			}
		endif;
		return apply_filters( 'bw_get_current_page_url', esc_url( $page_url ) );
	}

	function test_ajax_works() {
		if ( class_exists( 'Airplane_Mode_Core' ) ) {
			$airplane = Airplane_Mode_Core::getInstance();
			if ( method_exists( $airplane, 'enabled' ) ) {
				if ( $airplane->enabled() ) {
					return true;
				}
			} else {
				if ( $airplane->check_status() == 'on' ) {
					return true;
				}
			}
		}
		add_filter( 'block_local_requests', '__return_false' );
		if ( get_transient( '_bw_ajax_works' ) ) {
			return true;
		}
		$params = array(
			'sslverify'  => false,
			'timeout'    => 30,
			'body'       => array(
				'action' => 'bw_test_ajax'
			)
		);
		$ajax  = wp_remote_post( $this->get_ajax_url(), $params );
		$works = true;
		if ( is_wp_error( $ajax ) ) {
			$works = false;
		} else {
			if( empty( $ajax['response'] ) ) {
				$works = false;
			}
			if( empty( $ajax['response']['code'] ) || 200 !== (int) $ajax['response']['code'] ) {
				$works = false;
			}
			if( empty( $ajax['response']['message'] ) || 'OK' !== $ajax['response']['message'] ) {
				$works = false;
			}
			if( ! isset( $ajax['body'] ) || 0 !== (int) $ajax['body'] ) {
				$works = false;
			}
		}
		if ( $works ) {
			set_transient( '_bw_ajax_works', '1', DAY_IN_SECONDS );
		}
		return $works;
	}

	public function sysinfo_get() {
		global $wpdb;

		$browser = new BW_SysInfo_Browser();

		// Get theme info
		if ( get_bloginfo( 'version' ) < '3.4' ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
		} else {
			$theme_data = wp_get_theme();
			$theme      = $theme_data->Name . ' ' . $theme_data->Version;
		}

		$return = '';

		// Start with the basics...
		$return .= '<h3>Site Info</h3>';
		$return .= 'Site URL:                 ' . site_url() . "<br>";
		$return .= 'Home URL:                 ' . home_url() . "<br>";
		$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' );

		$return = apply_filters( 'bw_sysinfo_after_site_info', $return );

		// The local users' browser information, handled by the Browser class
		$return .= "<br>" . '<h3>User Browser</h3>';
		$return .= $browser;

		$return = apply_filters( 'bw_sysinfo_after_user_browser', $return );

		// WordPress configuration
		$return .= "<br>" . '<h3>Configuration</h3>';
		$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "<br>";
		$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "<br>";
		$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "<br>";
		$return .= 'Active Style:             ' . $theme . "<br>";
		$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "<br>";

		// Only show page specs if frontpage is set to 'page'
		if ( get_option( 'show_on_front' ) == 'page' ) {
			$front_page_id = get_option( 'page_on_front' );
			$blog_page_id  = get_option( 'page_for_posts' );

			$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "<br>";
			$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "<br>";
		}

		// Make sure wp_remote_post() is working
		/*
		$request['cmd'] = '_notify-validate';

		$params = array(
			'sslverify'  => false,
			'timeout'    => 60,
			'user-agent' => 'ePay/' . EPAY_VERSION,
			'body'       => $request
		);

		$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$WP_REMOTE_POST = 'wp_remote_post() works';
		} else {
			$WP_REMOTE_POST = 'wp_remote_post() does not work';
		}

		$return .= 'Remote Post:              ' . $WP_REMOTE_POST . "<br>";
		*/
		$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "<br>";
		$return .= 'Admin AJAX:               ' . ( $this->test_ajax_works() ? 'Accessible' : 'Inaccessible' ) . "<br>";
		$return .= 'DEBUG:                    ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "<br>";
		$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "<br>";
		$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() );

		$return = apply_filters( 'bw_sysinfo_after_wordpress_config', $return );


		// Must-use plugins
		$muplugins = get_mu_plugins();
		if ( count( $muplugins ) > 0  ) {
			$return .= "<br>" . '<h3>Must-Use Extensions</h3>';

			foreach ( $muplugins as $plugin => $plugin_data ) {
				$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "<br>";
			}

			$return = apply_filters( 'bw_sysinfo_after_wordpress_mu_plugins', $return );
		}

		// WordPress active plugins
		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		$return .= "<br>" . '<h3>Active Extensions</h3>';

		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( ! in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "<br>";
		}

		$return = apply_filters( 'bw_sysinfo_after_wordpress_plugins', $return );

		if(count ( array_diff( array_keys($plugins), $active_plugins) ) > 0 ){
			// WordPress inactive plugins
			$return .= "<br>" . '<h3>Inactive Extensions</h3>';

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( in_array( $plugin_path, $active_plugins ) ) {
					continue;
				}

				$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "<br>";
			}

			$return = apply_filters( 'bw_sysinfo_after_wordpress_plugins_inactive', $return );
		}

		if ( is_multisite() ) {
			// WordPress Multisite active plugins
			$return .= "<br>" . '<h3>Network Active Extensions</h3>';

			$plugins        = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

			foreach ( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );

				if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
					continue;
				}

				$plugin = get_plugin_data( $plugin_path );
				$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "<br>";
			}

			$return = apply_filters( 'bw_sysinfo_after_wordpress_ms_plugins', $return );
		}

		return $return;
	}

	public function sysinfo_get_server(){
		global $wpdb;

		// Try to identify the hosting provider
		$host = $this->get_host();

		$return = '';
		// Server configuration (really just versioning)
		$return .= '<h3>Webserver Configuration</h3>';
		$return .= 'PHP Version:              ' . PHP_VERSION . "<br>";
		$return .= 'MySQL Version:            ' . $wpdb->db_version() . "<br>";
		$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "<br>";

		$return = apply_filters( 'bw_sysinfo_after_webserver_config', $return );

		// Can we determine the site's host?
		if ( $host ) {
			$return .= "<br>" . '<h3>Provider</h3>';
			$return .= 'Host:                     ' . $host . "<br>";

			$return = apply_filters( 'bw_sysinfo_after_host_info', $return );
		}

		// PHP configs... now we're getting to the important stuff
		$return .= "<br>" . '<h3>PHP Configuration</h3>';
		$return .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "<br>" );
		$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "<br>";
		$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "<br>";
		$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "<br>";
		$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "<br>";
		$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "<br>";
		$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "<br>";
		$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "<br>";

		$return = apply_filters( 'bw_sysinfo_after_php_config', $return );

		// PHP extensions and such
		$return .= "<br>" . '<h3>PHP Extensions</h3>';
		$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "<br>";
		$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "<br>";
		$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "<br>";
		$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "<br>";

		$return = apply_filters( 'bw_sysinfo_after_php_ext', $return );

		// Session stuff
		$return .= "<br>" . '<h3>Session Configuration</h3>';
		$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "<br>";

		// The rest of this is only relevant is session is enabled
		if ( isset( $_SESSION ) ) {
			$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "<br>";
			$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "<br>";
			$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "<br>";
			$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "<br>";
			$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "<br>";
		}

		$return = apply_filters( 'bw_sysinfo_after_session_config', $return );

		return $return;
	}
}

class BW_SysInfo_Browser
{
    public $_agent = '';
    public $_browser_name = '';
    public $_version = '';
    public $_platform = '';
    public $_os = '';
    public $_is_aol = false;
    public $_is_mobile = false;
    public $_is_robot = false;
    public $_aol_version = '';

    public $BROWSER_UNKNOWN = 'unknown';
    public $VERSION_UNKNOWN = 'unknown';

    public $BROWSER_OPERA = 'Opera';
    public $BROWSER_OPERA_MINI = 'Opera Mini';
    public $BROWSER_WEBTV = 'WebTV';
    public $BROWSER_IE = 'Internet Explorer';
    public $BROWSER_POCKET_IE = 'Pocket Internet Explorer';
    public $BROWSER_KONQUEROR = 'Konqueror';
    public $BROWSER_ICAB = 'iCab';
    public $BROWSER_OMNIWEB = 'OmniWeb';
    public $BROWSER_FIREBIRD = 'Firebird';
    public $BROWSER_FIREFOX = 'Firefox';
    public $BROWSER_ICEWEASEL = 'Iceweasel';
    public $BROWSER_SHIRETOKO = 'Shiretoko';
    public $BROWSER_MOZILLA = 'Mozilla';
    public $BROWSER_AMAYA = 'Amaya';
    public $BROWSER_LYNX = 'Lynx';
    public $BROWSER_SAFARI = 'Safari';
    public $BROWSER_IPHONE = 'iPhone';
    public $BROWSER_IPOD = 'iPod';
    public $BROWSER_IPAD = 'iPad';
    public $BROWSER_CHROME = 'Chrome';
    public $BROWSER_ANDROID = 'Android';
    public $BROWSER_GOOGLEBOT = 'GoogleBot';
    public $BROWSER_SLURP = 'Yahoo! Slurp';
    public $BROWSER_W3CVALIDATOR = 'W3C Validator';
    public $BROWSER_BLACKBERRY = 'BlackBerry';
    public $BROWSER_ICECAT = 'IceCat';
    public $BROWSER_NOKIA_S60 = 'Nokia S60 OSS Browser';
    public $BROWSER_NOKIA = 'Nokia Browser';
    public $BROWSER_MSN = 'MSN Browser';
    public $BROWSER_MSNBOT = 'MSN Bot';

    public $BROWSER_NETSCAPE_NAVIGATOR = 'Netscape Navigator';
    public $BROWSER_GALEON = 'Galeon';
    public $BROWSER_NETPOSITIVE = 'NetPositive';
    public $BROWSER_PHOENIX = 'Phoenix';

    public $PLATFORM_UNKNOWN = 'unknown';
    public $PLATFORM_WINDOWS = 'Windows';
    public $PLATFORM_WINDOWS_CE = 'Windows CE';
    public $PLATFORM_APPLE = 'Apple';
    public $PLATFORM_LINUX = 'Linux';
    public $PLATFORM_OS2 = 'OS/2';
    public $PLATFORM_BEOS = 'BeOS';
    public $PLATFORM_IPHONE = 'iPhone';
    public $PLATFORM_IPOD = 'iPod';
    public $PLATFORM_IPAD = 'iPad';
    public $PLATFORM_BLACKBERRY = 'BlackBerry';
    public $PLATFORM_NOKIA = 'Nokia';
    public $PLATFORM_FREEBSD = 'FreeBSD';
    public $PLATFORM_OPENBSD = 'OpenBSD';
    public $PLATFORM_NETBSD = 'NetBSD';
    public $PLATFORM_SUNOS = 'SunOS';
    public $PLATFORM_OPENSOLARIS = 'OpenSolaris';
    public $PLATFORM_ANDROID = 'Android';

    public $OPERATING_SYSTEM_UNKNOWN = 'unknown';

    function BW_SysInfo_Browser($useragent = "") {
        $this->reset();
        if ($useragent != "") {
            $this->setUserAgent($useragent);
        }
        else {
            $this->determine();
        }
    }

    function reset() {
        $this->_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        $this->_browser_name = $this->BROWSER_UNKNOWN;
        $this->_version = $this->VERSION_UNKNOWN;
        $this->_platform = $this->PLATFORM_UNKNOWN;
        $this->_os = $this->OPERATING_SYSTEM_UNKNOWN;
        $this->_is_aol = false;
        $this->_is_mobile = false;
        $this->_is_robot = false;
        $this->_aol_version = $this->VERSION_UNKNOWN;
    }

    function isBrowser($browserName) {
        return (0 == strcasecmp($this->_browser_name, trim($browserName)));
    }

    function getBrowser() {
        return $this->_browser_name;
    }

    function setBrowser($browser) {
        return $this->_browser_name = $browser;
    }

    function getPlatform() {
        return $this->_platform;
    }

    function setPlatform($platform) {
        return $this->_platform = $platform;
    }

    function getVersion() {
        return $this->_version;
    }

    function setVersion($version) {
        $this->_version = preg_replace('/[^0-9,.,a-z,A-Z-]/', '', $version);
    }

    function getAolVersion() {
        return $this->_aol_version;
    }

    function setAolVersion($version) {
        $this->_aol_version = preg_replace('/[^0-9,.,a-z,A-Z]/', '', $version);
    }

    function isAol() {
        return $this->_is_aol;
    }

    function isMobile() {
        return $this->_is_mobile;
    }

    function isRobot() {
        return $this->_is_robot;
    }

    function setAol($isAol) {
        $this->_is_aol = $isAol;
    }

    function setMobile($value = true) {
        $this->_is_mobile = $value;
    }

    function setRobot($value = true) {
        $this->_is_robot = $value;
    }

    function getUserAgent() {
        return $this->_agent;
    }

    function setUserAgent($agent_string) {
        $this->reset();
        $this->_agent = $agent_string;
        $this->determine();
    }

    function isChromeFrame() {
        return (strpos($this->_agent, "chromeframe") !== false);
    }

    function __toString() {
        $text1 = $this->getUserAgent();
        $UAline1 = substr($text1, 0, 32);
        $text2 = $this->getUserAgent();
        $towrapUA = str_replace($UAline1, '', $text2);
        $space = '';
        for ($i = 0; $i < 25; $i++) {
            $space.= ' ';
        }

        $wordwrapped = chunk_split($towrapUA, 32, "\n $space");
        return "Platform:                 {$this->getPlatform() } <br>" . "Browser Name:             {$this->getBrowser() }  <br>" . "Browser Version:          {$this->getVersion() } <br>" . "User Agent String:        $UAline1 \n\t\t\t  " . "$wordwrapped";
    }

    function determine() {
        $this->checkPlatform();
        $this->checkBrowsers();
        $this->checkForAol();
    }

    function checkBrowsers() {
        return (
        $this->checkBrowserWebTv() || $this->checkBrowserInternetExplorer() || $this->checkBrowserOpera() || $this->checkBrowserGaleon() || $this->checkBrowserNetscapeNavigator9Plus() || $this->checkBrowserFirefox() || $this->checkBrowserChrome() || $this->checkBrowserOmniWeb() ||
        $this->checkBrowserAndroid() || $this->checkBrowseriPad() || $this->checkBrowseriPod() || $this->checkBrowseriPhone() || $this->checkBrowserBlackBerry() || $this->checkBrowserNokia() ||
        $this->checkBrowserGoogleBot() || $this->checkBrowserMSNBot() || $this->checkBrowserSlurp() ||
        $this->checkBrowserSafari() ||
        $this->checkBrowserNetPositive() || $this->checkBrowserFirebird() || $this->checkBrowserKonqueror() || $this->checkBrowserIcab() || $this->checkBrowserPhoenix() || $this->checkBrowserAmaya() || $this->checkBrowserLynx() || $this->checkBrowserShiretoko() || $this->checkBrowserIceCat() || $this->checkBrowserW3CValidator() || $this->checkBrowserMozilla());
    }

    function checkBrowserBlackBerry() {
        if (stripos($this->_agent, 'blackberry') !== false) {
            $aresult = explode("/", stristr($this->_agent, "BlackBerry"));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->_browser_name = $this->BROWSER_BLACKBERRY;
            $this->setMobile(true);
            return true;
        }
        return false;
    }

    function checkForAol() {
        $this->setAol(false);
        $this->setAolVersion($this->VERSION_UNKNOWN);

        if (stripos($this->_agent, 'aol') !== false) {
            $aversion = explode(' ', stristr($this->_agent, 'AOL'));
            $this->setAol(true);
            $this->setAolVersion(preg_replace('/[^0-9\.a-z]/i', '', $aversion[1]));
            return true;
        }
        return false;
    }

    function checkBrowserGoogleBot() {
        if (stripos($this->_agent, 'googlebot') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'googlebot'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion(str_replace(';', '', $aversion[0]));
            $this->_browser_name = $this->BROWSER_GOOGLEBOT;
            $this->setRobot(true);
            return true;
        }
        return false;
    }

    function checkBrowserMSNBot() {
        if (stripos($this->_agent, "msnbot") !== false) {
            $aresult = explode("/", stristr($this->_agent, "msnbot"));
            $aversion = explode(" ", $aresult[1]);
            $this->setVersion(str_replace(";", "", $aversion[0]));
            $this->_browser_name = $this->BROWSER_MSNBOT;
            $this->setRobot(true);
            return true;
        }
        return false;
    }

    function checkBrowserW3CValidator() {
        if (stripos($this->_agent, 'W3C-checklink') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'W3C-checklink'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->_browser_name = $this->BROWSER_W3CVALIDATOR;
            return true;
        }
        else if (stripos($this->_agent, 'W3C_Validator') !== false) {

            $ua = str_replace("W3C_Validator ", "W3C_Validator/", $this->_agent);
            $aresult = explode('/', stristr($ua, 'W3C_Validator'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->_browser_name = $this->BROWSER_W3CVALIDATOR;
            return true;
        }
        return false;
    }

    function checkBrowserSlurp() {
        if (stripos($this->_agent, 'slurp') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Slurp'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->_browser_name = $this->BROWSER_SLURP;
            $this->setRobot(true);
            $this->setMobile(false);
            return true;
        }
        return false;
    }

    function checkBrowserInternetExplorer() {

        if (stripos($this->_agent, 'microsoft internet explorer') !== false) {
            $this->setBrowser($this->BROWSER_IE);
            $this->setVersion('1.0');
            $aresult = stristr($this->_agent, '/');
            if (preg_match('/308|425|426|474|0b1/i', $aresult)) {
                $this->setVersion('1.5');
            }
            return true;
        }
        else if (stripos($this->_agent, 'msie') !== false && stripos($this->_agent, 'opera') === false) {

            if (stripos($this->_agent, 'msnb') !== false) {
                $aresult = explode(' ', stristr(str_replace(';', '; ', $this->_agent) , 'MSN'));
                $this->setBrowser($this->BROWSER_MSN);
                $this->setVersion(str_replace(array(
                    '(',
                    ')',
                    ';'
                ) , '', $aresult[1]));
                return true;
            }
            $aresult = explode(' ', stristr(str_replace(';', '; ', $this->_agent) , 'msie'));
            $this->setBrowser($this->BROWSER_IE);
            $this->setVersion(str_replace(array(
                '(',
                ')',
                ';'
            ) , '', $aresult[1]));
            return true;
        }
        else if (stripos($this->_agent, 'mspie') !== false || stripos($this->_agent, 'pocket') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'mspie'));
            $this->setPlatform($this->PLATFORM_WINDOWS_CE);
            $this->setBrowser($this->BROWSER_POCKET_IE);
            $this->setMobile(true);

            if (stripos($this->_agent, 'mspie') !== false) {
                $this->setVersion($aresult[1]);
            }
            else {
                $aversion = explode('/', $this->_agent);
                $this->setVersion($aversion[1]);
            }
            return true;
        }
        return false;
    }

    function checkBrowserOpera() {
        if (stripos($this->_agent, 'opera mini') !== false) {
            $resultant = stristr($this->_agent, 'opera mini');
            if (preg_match('/\//', $resultant)) {
                $aresult = explode('/', $resultant);
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            }
            else {
                $aversion = explode(' ', stristr($resultant, 'opera mini'));
                $this->setVersion($aversion[1]);
            }
            $this->_browser_name = $this->BROWSER_OPERA_MINI;
            $this->setMobile(true);
            return true;
        }
        else if (stripos($this->_agent, 'opera') !== false) {
            $resultant = stristr($this->_agent, 'opera');
            if (preg_match('/Version\/(10.*)$/', $resultant, $matches)) {
                $this->setVersion($matches[1]);
            }
            else if (preg_match('/\//', $resultant)) {
                $aresult = explode('/', str_replace("(", " ", $resultant));
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            }
            else {
                $aversion = explode(' ', stristr($resultant, 'opera'));
                $this->setVersion(isset($aversion[1]) ? $aversion[1] : "");
            }
            $this->_browser_name = $this->BROWSER_OPERA;
            return true;
        }
        return false;
    }

    function checkBrowserChrome() {
        if (stripos($this->_agent, 'Chrome') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Chrome'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_CHROME);
            return true;
        }
        return false;
    }

    function checkBrowserWebTv() {
        if (stripos($this->_agent, 'webtv') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'webtv'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_WEBTV);
            return true;
        }
        return false;
    }

    function checkBrowserNetPositive() {
        if (stripos($this->_agent, 'NetPositive') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'NetPositive'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion(str_replace(array(
                '(',
                ')',
                ';'
            ) , '', $aversion[0]));
            $this->setBrowser($this->BROWSER_NETPOSITIVE);
            return true;
        }
        return false;
    }

    function checkBrowserGaleon() {
        if (stripos($this->_agent, 'galeon') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'galeon'));
            $aversion = explode('/', $aresult[0]);
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_GALEON);
            return true;
        }
        return false;
    }

    function checkBrowserKonqueror() {
        if (stripos($this->_agent, 'Konqueror') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'Konqueror'));
            $aversion = explode('/', $aresult[0]);
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_KONQUEROR);
            return true;
        }
        return false;
    }

    function checkBrowserIcab() {
        if (stripos($this->_agent, 'icab') !== false) {
            $aversion = explode(' ', stristr(str_replace('/', ' ', $this->_agent) , 'icab'));
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_ICAB);
            return true;
        }
        return false;
    }

    function checkBrowserOmniWeb() {
        if (stripos($this->_agent, 'omniweb') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'omniweb'));
            $aversion = explode(' ', isset($aresult[1]) ? $aresult[1] : "");
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_OMNIWEB);
            return true;
        }
        return false;
    }

    function checkBrowserPhoenix() {
        if (stripos($this->_agent, 'Phoenix') !== false) {
            $aversion = explode('/', stristr($this->_agent, 'Phoenix'));
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_PHOENIX);
            return true;
        }
        return false;
    }

    function checkBrowserFirebird() {
        if (stripos($this->_agent, 'Firebird') !== false) {
            $aversion = explode('/', stristr($this->_agent, 'Firebird'));
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_FIREBIRD);
            return true;
        }
        return false;
    }

    function checkBrowserNetscapeNavigator9Plus() {
        if (stripos($this->_agent, 'Firefox') !== false && preg_match('/Navigator\/([^ ]*)/i', $this->_agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_NETSCAPE_NAVIGATOR);
            return true;
        }
        else if (stripos($this->_agent, 'Firefox') === false && preg_match('/Netscape6?\/([^ ]*)/i', $this->_agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_NETSCAPE_NAVIGATOR);
            return true;
        }
        return false;
    }

    function checkBrowserShiretoko() {
        if (stripos($this->_agent, 'Mozilla') !== false && preg_match('/Shiretoko\/([^ ]*)/i', $this->_agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_SHIRETOKO);
            return true;
        }
        return false;
    }

    function checkBrowserIceCat() {
        if (stripos($this->_agent, 'Mozilla') !== false && preg_match('/IceCat\/([^ ]*)/i', $this->_agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_ICECAT);
            return true;
        }
        return false;
    }

    function checkBrowserNokia() {
        if (preg_match("/Nokia([^\/]+)\/([^ SP]+)/i", $this->_agent, $matches)) {
            $this->setVersion($matches[2]);
            if (stripos($this->_agent, 'Series60') !== false || strpos($this->_agent, 'S60') !== false) {
                $this->setBrowser($this->BROWSER_NOKIA_S60);
            }
            else {
                $this->setBrowser($this->BROWSER_NOKIA);
            }
            $this->setMobile(true);
            return true;
        }
        return false;
    }

    function checkBrowserFirefox() {
        if (stripos($this->_agent, 'safari') === false) {
            if (preg_match("/Firefox[\/ \(]([^ ;\)]+)/i", $this->_agent, $matches)) {
                $this->setVersion($matches[1]);
                $this->setBrowser($this->BROWSER_FIREFOX);
                return true;
            }
            else if (preg_match("/Firefox$/i", $this->_agent, $matches)) {
                $this->setVersion("");
                $this->setBrowser($this->BROWSER_FIREFOX);
                return true;
            }
        }
        return false;
    }

    function checkBrowserIceweasel() {
        if (stripos($this->_agent, 'Iceweasel') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Iceweasel'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_ICEWEASEL);
            return true;
        }
        return false;
    }

    function checkBrowserMozilla() {
        if (stripos($this->_agent, 'mozilla') !== false && preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->_agent) && stripos($this->_agent, 'netscape') === false) {
            $aversion = explode(' ', stristr($this->_agent, 'rv:'));
            preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->_agent, $aversion);
            $this->setVersion(str_replace('rv:', '', $aversion[0]));
            $this->setBrowser($this->BROWSER_MOZILLA);
            return true;
        }
        else if (stripos($this->_agent, 'mozilla') !== false && preg_match('/rv:[0-9]\.[0-9]/i', $this->_agent) && stripos($this->_agent, 'netscape') === false) {
            $aversion = explode('', stristr($this->_agent, 'rv:'));
            $this->setVersion(str_replace('rv:', '', $aversion[0]));
            $this->setBrowser($this->BROWSER_MOZILLA);
            return true;
        }
        else if (stripos($this->_agent, 'mozilla') !== false && preg_match('/mozilla\/([^ ]*)/i', $this->_agent, $matches) && stripos($this->_agent, 'netscape') === false) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_MOZILLA);
            return true;
        }
        return false;
    }

    function checkBrowserLynx() {
        if (stripos($this->_agent, 'lynx') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Lynx'));
            $aversion = explode(' ', (isset($aresult[1]) ? $aresult[1] : ""));
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_LYNX);
            return true;
        }
        return false;
    }

    function checkBrowserAmaya() {
        if (stripos($this->_agent, 'amaya') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Amaya'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_AMAYA);
            return true;
        }
        return false;
    }

    function checkBrowserSafari() {
        if (stripos($this->_agent, 'Safari') !== false && stripos($this->_agent, 'iPhone') === false && stripos($this->_agent, 'iPod') === false) {
            $aresult = explode('/', stristr($this->_agent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            }
            else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setBrowser($this->BROWSER_SAFARI);
            return true;
        }
        return false;
    }

    function checkBrowseriPhone() {
        if (stripos($this->_agent, 'iPhone') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            }
            else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setMobile(true);
            $this->setBrowser($this->BROWSER_IPHONE);
            return true;
        }
        return false;
    }

    function checkBrowseriPad() {
        if (stripos($this->_agent, 'iPad') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            }
            else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setMobile(true);
            $this->setBrowser($this->BROWSER_IPAD);
            return true;
        }
        return false;
    }

    function checkBrowseriPod() {
        if (stripos($this->_agent, 'iPod') !== false) {
            $aresult = explode('/', stristr($this->_agent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            }
            else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setMobile(true);
            $this->setBrowser($this->BROWSER_IPOD);
            return true;
        }
        return false;
    }

    function checkBrowserAndroid() {
        if (stripos($this->_agent, 'Android') !== false) {
            $aresult = explode(' ', stristr($this->_agent, 'Android'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            }
            else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setMobile(true);
            $this->setBrowser($this->BROWSER_ANDROID);
            return true;
        }
        return false;
    }

    function checkPlatform() {
        if (stripos($this->_agent, 'windows') !== false) {
            $this->_platform = $this->PLATFORM_WINDOWS;
        }
        else if (stripos($this->_agent, 'iPad') !== false) {
            $this->_platform = $this->PLATFORM_IPAD;
        }
        else if (stripos($this->_agent, 'iPod') !== false) {
            $this->_platform = $this->PLATFORM_IPOD;
        }
        else if (stripos($this->_agent, 'iPhone') !== false) {
            $this->_platform = $this->PLATFORM_IPHONE;
        }
        elseif (stripos($this->_agent, 'mac') !== false) {
            $this->_platform = $this->PLATFORM_APPLE;
        }
        elseif (stripos($this->_agent, 'android') !== false) {
            $this->_platform = $this->PLATFORM_ANDROID;
        }
        elseif (stripos($this->_agent, 'linux') !== false) {
            $this->_platform = $this->PLATFORM_LINUX;
        }
        else if (stripos($this->_agent, 'Nokia') !== false) {
            $this->_platform = $this->PLATFORM_NOKIA;
        }
        else if (stripos($this->_agent, 'BlackBerry') !== false) {
            $this->_platform = $this->PLATFORM_BLACKBERRY;
        }
        elseif (stripos($this->_agent, 'FreeBSD') !== false) {
            $this->_platform = $this->PLATFORM_FREEBSD;
        }
        elseif (stripos($this->_agent, 'OpenBSD') !== false) {
            $this->_platform = $this->PLATFORM_OPENBSD;
        }
        elseif (stripos($this->_agent, 'NetBSD') !== false) {
            $this->_platform = $this->PLATFORM_NETBSD;
        }
        elseif (stripos($this->_agent, 'OpenSolaris') !== false) {
            $this->_platform = $this->PLATFORM_OPENSOLARIS;
        }
        elseif (stripos($this->_agent, 'SunOS') !== false) {
            $this->_platform = $this->PLATFORM_SUNOS;
        }
        elseif (stripos($this->_agent, 'OS\/2') !== false) {
            $this->_platform = $this->PLATFORM_OS2;
        }
        elseif (stripos($this->_agent, 'BeOS') !== false) {
            $this->_platform = $this->PLATFORM_BEOS;
        }
        elseif (stripos($this->_agent, 'win') !== false) {
            $this->_platform = $this->PLATFORM_WINDOWS;
        }
    }
}

new BW_Dashboard_Widget_SysInfo;
