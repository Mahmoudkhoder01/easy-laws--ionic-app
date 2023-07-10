<?php

!defined('ABSPATH') && exit;

class BW_Block_Robots {

    public $referlist, $botlist;
    protected static $_instance = null;
    public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct() {
        $this->referlist = array(
            'semalt.com',
            'kambasoft.com',
            'savetubevideo.com',
            'buttons-for-website.com',
            'sharebutton.net',
            'soundfrost.org',
            'srecorder.com',
            'softomix.com',
            'softomix.net',
            'myprintscreen.com',
            'joinandplay.me',
            'fbfreegifts.com',
            'openmediasoft.com',
            'zazagames.org',
            'extener.org',
            'openfrost.com',
            'openfrost.net',
            'googlsucks.com',
            'best-seo-offer.com',
            'buttons-for-your-website.com',
            'www.Get-Free-Traffic-Now.com',
            'best-seo-solution.com',
            'buy-cheap-online.info',
            'site3.free-share-buttons.com',
            'webmaster-traffic.com',
            'law-enforcement-bot-ii.xyz',
            'cookie-law-enforcement-cc.xyz',
            'social-traffic-5.xyz',
            'free-social-buttons.xyz',
            'free-social-buttons1.xyz',
            'free-social-buttons2.xyz',
            'free-social-buttons3.xyz',
            'free-social-buttons4.xyz',
            'free-social-buttons5.xyz',
            'free-social-buttons6.xyz',
            'free-social-buttons7.xyz',
            'fix-website-errors.com',
            'keywords-monitoring-your-success.com',
            'free-video-tool.com',
            'keywords-monitoring-success.com',
        );
        $this->botlist = array(
            "Abonti",
            "aggregator",
            "AhrefsBot",
            "asterias",
            "BDCbot",
            "BLEXBot",
            "BuiltBotTough",
            "Bullseye",
            "BunnySlippers",
            "ca-crawler",
            "CCBot",
            "Cegbfeieh",
            "CheeseBot",
            "CherryPicker",
            "CopyRightCheck",
            "cosmos",
            "Crescent",
            "discobot",
            "DittoSpyder",
            "DotBot",
            "Download Ninja",
            "EasouSpider",
            "EmailCollector",
            "EmailSiphon",
            "EmailWolf",
            "EroCrawler",
            "Exabot",
            "ExtractorPro",
            "Fasterfox",
            "FeedBooster",
            "Foobot",
            "Genieo",
            "grub-client",
            "Harvest",
            "hloader",
            "httplib",
            "HTTrack",
            "humanlinks",
            "ieautodiscovery",
            "InfoNaviRobot",
            "IstellaBot",
            "Java/1.",
            "JennyBot",
            "k2spider",
            "Kenjin Spider",
            "Keyword Density/0.9",
            "larbin",
            "LexiBot",
            "libWeb",
            "libwww",
            "LinkextractorPro",
            "linko",
            "LinkScan/8.1a Unix",
            "LinkWalker",
            "LNSpiderguy",
            "lwp-trivial",
            "magpie",
            "Mata Hari",
            'MaxPointCrawler',
            'MegaIndex',
            "Microsoft URL Control",
            "MIIxpc",
            "Mippin",
            "Missigua Locator",
            "Mister PiX",
            "MJ12bot",
            "moget",
            "MSIECrawler",
            "NetAnts",
            "NICErsPRO",
            "Niki-Bot",
            "NPBot",
            "Nutch",
            "Offline Explorer",
            "Openfind",
            'panscient.com',
            "PHP/5.{",
            "ProPowerBot/2.14",
            "ProWebWalker",
            "Python-urllib",
            "QueryN Metasearch",
            "RepoMonkey",
            "RMA",
            'SemrushBot',
            "SeznamBot",
            "SISTRIX",
            "sitecheck.Internetseer.com",
            "SiteSnagger",
            "SnapPreviewBot",
            "Sogou",
            "SpankBot",
            "spanner",
            "spbot",
            "Spinn3r",
            "suzuran",
            "Szukacz/1.4",
            "Teleport",
            "Telesoft",
            "The Intraformant",
            "TheNomad",
            "TightTwatBot",
            "Titan",
            "toCrawl/UrlDispatcher",
            "True_Robot",
            "turingos",
            "TurnitinBot",
            "UbiCrawler",
            "UnisterBot",
            "URLy Warning",
            "VCI",
            "WBSearchBot",
            "Web Downloader/6.9",
            "Web Image Collector",
            "WebAuto",
            "WebBandit",
            "WebCopier",
            "WebEnhancer",
            "WebmasterWorldForumBot",
            "WebReaper",
            "WebSauger",
            "Website Quester",
            "Webster Pro",
            "WebStripper",
            "WebZip",
            "Wotbox",
            "wsr-agent",
            "WWW-Collector-E",
            "Xenu",
            "Zao",
            "Zeus",
            "ZyBORG",
            'coccoc',
            'Incutio',
            'lmspider',
            'memoryBot',
            'SemrushBot',
            'serf',
            'Unknown',
            'uptime files',
        );
    }

    public function init(){
    	if(is_admin()) return;
    	if(!bwd_get_option('block_bots')){
	        if ( !$this->allow_bot() ) {
				$ip = $_SERVER['REMOTE_ADDR'];
				$user_agent = $_SERVER['HTTP_USER_AGENT'];
				bw_die( sprintf( __( "Blocked bot with IP %s -- matched user agent %s found in blocklist.", BW_TD ), $ip, $user_agent ), 503 );
				exit();
			} elseif ( $this->is_bad_referer() ) {
				$ip = $_SERVER['REMOTE_ADDR'];
				$referer = $_SERVER['HTTP_REFERER'];
				bw_die( sprintf( __( "Blocked bot with IP %s -- matched referer %s found in blocklist.", BW_TD ), $ip, $referer ), 503 );
			}
		}
    }

    function quote_list_for_regex( $list, $quote = '/' ) {
		$regex = '';
		$cont = 0;
		foreach( $list as $l ) {
			if ( $cont ) $regex .= '|';
			$cont = 1;
			$regex .= preg_quote( trim( $l ), $quote );
		}
		return $regex;
	}

	function is_good_bot() {
		$botlist = Array(
			"Yahoo! Slurp" => "crawl.yahoo.net",
			"googlebot" => ".googlebot.com",
			"msnbot" => "search.msn.com"
		);
		$botlist = apply_filters( "bw_goodbotlist", $botlist );
		if ( !empty( $botlist ) ) {
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$uas = $this->quote_list_for_regex( $botlist );
			if ( preg_match( '/' . $uas . '/i', $ua ) ) {
				$ip = $_SERVER['REMOTE_ADDR'];
				$hostname = gethostbyaddr( $ip );
				$ip_by_hostname = gethostbyname( $hostname );
			    if ( $ip_by_hostname == $ip ) {
					$hosts = array_values( $botlist );
					foreach( $hosts as $k => $h )
						$hosts[$k] = preg_quote( $h ) . '$';
					$hosts = join( '|', $hosts );
					if ( preg_match( '/' . $hosts . '/i', $hostname ) )
						return true;
				}
			}
			return false;
		}
	}

	function is_bad_bot() {
		$botlist = $this->botlist;
		$botlist = apply_filters( "bw_badbotlist", $botlist );
		if ( !empty( $botlist ) ) {
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$uas = $this->quote_list_for_regex( $botlist );
			if ( preg_match( '/' . $uas . '/i', $ua ) ) {
				return true;
			}
		}
		return false;
	}

    function is_bad_referer() {
		$referlist = $this->referlist;
		$referlist = apply_filters( "bw_badreferlist", $referlist );

		if ( !empty( $referlist ) && !empty( $_SERVER ) && !empty( $_SERVER['HTTP_REFERER'] ) ) {
			$ref = $_SERVER['HTTP_REFERER'];
			$regex = $this->quote_list_for_regex( $referlist );
			if ( preg_match( '/' . $regex . '/i', $ref ) ) return true;
		}
		return false;
	}

	function allow_bot() {
		$allow_bot = true;
		if ( ( !$this->is_good_bot() ) && ( $this->is_bad_bot() ) )
			$allow_bot = false;
		return apply_filters( "bw_allow_bot", $allow_bot );
	}
}

function BW_Block_Robots(){return BW_Block_Robots::instance();}
$GLOBALS['BW_Block_Robots'] = BW_Block_Robots();
BW_Block_Robots()->init();
