<?php
if (!function_exists("get_option")) die();

define('BW_CAPSMAN_VERSION', '4.18.4');
define('BW_CAPSMAN_PLUGIN_URL', plugins_url('/', __FILE__));
define('BW_CAPSMAN_PLUGIN_DIR', dirname(__FILE__).'/');
define('BW_CAPSMAN_PLUGIN_BASE_NAME', basename(__FILE__));
define('BW_CAPSMAN_PLUGIN_FILE', basename(__FILE__));
define('BW_CAPSMAN_PLUGIN_FULL_PATH', __FILE__);

require_once(dirname(__FILE__) . '/includes/class-bw-capsman-main-lib.php');
require_once(dirname(__FILE__) . '/includes/class-bw-capsman-lib.php');

define('BW_CAPSMAN_WP_ADMIN_URL', admin_url());
define('BW_CAPSMAN_ERROR', 'Error is encountered');
define('BW_CAPSMAN_SPACE_REPLACER', '_bw-capsman-SR_');
define('BW_CAPSMAN_PARENT', is_network_admin() ? 'network/users.php':'users.php');
define('BW_CAPSMAN_KEY_CAPABILITY', 'can_bitwize');
define('BW_CAPSMAN_SHOW_ADMIN_ROLE', 1); // enforce administrator access

require_once( dirname(__FILE__) . '/includes/class-bw-capsman.php');

$bw_capsman_lib = new BW_Capsman_Lib('bw_capsman');
$GLOBALS['bw_capsman'] = new BW_Capsman($bw_capsman_lib);
