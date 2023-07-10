<?php

defined('ABSPATH') or exit;

final class __Cache{

    public static $options;
    private static $disk;
    const MINIFY_DISABLED  = 0;
    const MINIFY_HTML_ONLY = 1;
    const MINIFY_HTML_JS   = 2;

    public function __construct() {
		define('CE_BASE', plugin_basename(BITWIZE_CORE_PLUGIN_FILE));
		define('CE_CACHE_DIR', WP_CONTENT_DIR. '/cache/sys-cache');

        if( get_option('bw_cache_nohtaccess_status') == 1 ){
        	add_action( 'plugins_loaded', array( __CLASS__, '__init' ));
        }
        add_action('admin_init', array( __CLASS__, 'admin_init'));
    }

    public static function admin_init(){
        if(defined('DOING_AJAX') || defined('DOING_CRON')) return;
        if(!current_user_can('can_bitwize')) return;
        if(isset($_GET['bw_cache'])){
            if($_GET['bw_cache'] == 'activate_nohtaccess'){
                self::on_activation();
                wp_redirect( admin_url('options-general.php?page=BWDO') );
                exit;
            }
            if($_GET['bw_cache'] == 'deactivate_nohtaccess'){
                self::on_deactivation();
                wp_redirect( admin_url('options-general.php?page=BWDO') );
                exit;
            }
        }
    }

    public static function __init(){
        self::_set_default_vars();

        add_action( 'init', array( __CLASS__, 'register_publish_hooks'), 99);
        add_action( 'ce_clear_post_cache', array(__CLASS__,'clear_page_cache_by_post_id', ) );
        add_action( 'ce_clear_cache', array( __CLASS__, 'clear_total_cache' ) );
        add_action( '_core_updated_successfully', array( __CLASS__, 'clear_total_cache' ) );
        add_action( 'switch_theme', array( __CLASS__, 'clear_total_cache' ) );
        add_action( 'wp_trash_post', array( __CLASS__, 'clear_total_cache' ) );
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_links' ), 90 );
        add_action( 'init', array( __CLASS__, 'process_clear_request' ) );

        if (is_admin()) {
            add_action( 'wpmu_new_blog', array( __CLASS__, 'install_later' ) );
            add_action( 'delete_blog', array( __CLASS__, 'uninstall_later' ) );
            add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
            add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_admin_resources' ) );
            add_action( 'transition_comment_status', array( __CLASS__, 'change_comment' ), 10, 3 );
            add_action( 'edit_comment', array( __CLASS__, 'edit_comment', ) );
            add_filter( 'dashboard_glance_items', array( __CLASS__, 'add_dashboard_count' ) );
            add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'add_clear_dropdown' ) );
            add_action('admin_notices',array(__CLASS__,'warning_is_permalink',));

        } else {
            add_action('pre_comment_approved',array(__CLASS__,'new_comment'),99,2);
            add_action('template_redirect',array(__CLASS__,'handle_cache'),0);
        }
    }

    public static function on_deactivation(){
        delete_option( 'cache' );
        update_option('bw_cache_nohtaccess_status', '0');
        self::clear_total_cache(true);
    }

    public static function on_activation()
    {
        update_option('bw_cache_nohtaccess_status', '1');
        if (is_multisite() && !empty($_GET['networkwide'])) {
            $ids = self::_get_blog_ids();

            foreach ($ids as $id) {
                switch_to_blog($id);
                self::_install_backend();
            }

            restore_current_blog();

        } else {
            self::_install_backend();
        }
    }

    public static function install_later($id){
        if (!is_plugin_active_for_network(CE_BASE)) {
            return;
        }
        switch_to_blog($id);
        self::_install_backend();
        restore_current_blog();
    }

    private static function _install_backend(){
        add_option( 'cache', array() );
        self::clear_total_cache(true);
    }

    public static function on_uninstall(){
        global $wpdb;

        if (is_multisite() && !empty($_GET['networkwide'])) {
            $old = $wpdb->blogid;

            $ids = self::_get_blog_ids();

            foreach ($ids as $id) {
                switch_to_blog($id);
                self::_uninstall_backend();
            }

            switch_to_blog($old);
        } else {
            self::_uninstall_backend();
        }
    }

    public static function uninstall_later($id){

        if (!is_plugin_active_for_network(CE_BASE)) {
            return;
        }
        switch_to_blog($id);
        self::_uninstall_backend();
        restore_current_blog();
    }

    private static function _uninstall_backend(){
        delete_option('cache');
        self::clear_total_cache(true);
    }

    private static function _get_blog_ids(){
        global $wpdb;
        return $wpdb->get_col("SELECT blog_id FROM `$wpdb->blogs`");
    }

    private static function _set_default_vars(){
        self::$options = self::_get_options();
        if (__Cache_Disk::is_permalink()) {
            self::$disk = new __Cache_Disk;
        }
    }

    private static function _get_options(){
        return wp_parse_args(
            get_option('cache'),
            array(
                'expires'     => 0,
                'new_post'    => 1,
                'new_comment' => 1,
                'compress'    => 0,
                'webp'        => 0,
                'excl_ids'    => '',
                'minify_html' => self::MINIFY_DISABLED,
            )
        );
    }

    public static function warning_is_permalink(){
        if (!__Cache_Disk::is_permalink() and current_user_can('manage_options')) {?>
			<div class="error">
				<p><?php printf(__('<b>Cache</b> requires a custom permalink structure to start caching properly. Please go to <a href="%s">Permalink</a> to enable it.', 'cache'), admin_url('options-permalink.php'));?></p>
			</div>
		<?php
}
    }

    public static function add_dashboard_count($items = array()){
        if (!current_user_can('manage_options')) {
            return $items;
        }
        $size = self::get_cache_size();
        $items[] = sprintf(
            '<a href="%s" title="Disk Cache">%s Cache Size</a>',
            add_query_arg(
                array(
                    'page' => '__cache',
                ),
                admin_url('options-general.php')
            ),
            (empty($size) ? esc_html__('Empty', 'cache') : size_format($size))
        );
        return $items;
    }

    public static function get_cache_size(){
        if (!$size = get_transient('cache_size')) {
            $size = (int) self::$disk->cache_size(CE_CACHE_DIR);
            set_transient('cache_size',$size,60 * 15);
        }
        return $size;
    }

    public static function add_admin_links($wp_admin_bar){
        if (!is_admin_bar_showing() or !apply_filters('user_can_clear_cache', current_user_can('manage_options'))) {
            return;
        }

        $wp_admin_bar->add_menu(
            array(
                'id'     => 'clear-cache',
                'href'   => wp_nonce_url(add_query_arg('_cache', 'clear'), '_cache__clear_nonce'),
                'parent' => 'top-secondary',
                'title'  => '<i class="fa fa-trash-o"></i> Cache',
                'meta'   => array('title' => 'Clear Cache'),
            )
        );

    }

    public static function process_clear_request($data){
        if (empty($_GET['_cache']) or $_GET['_cache'] !== 'clear') {
            return;
        }
        if (empty($_GET['_wpnonce']) or !wp_verify_nonce($_GET['_wpnonce'], '_cache__clear_nonce')) {
            return;
        }
        if (!is_admin_bar_showing() or !apply_filters('user_can_clear_cache', current_user_can('manage_options'))) {
            return;
        }
        if (!function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (is_multisite() && is_plugin_active_for_network(CE_BASE)) {
            $legacy = $GLOBALS['wpdb']->blogid;
            $ids = self::_get_blog_ids();
            foreach ($ids as $id) {
                switch_to_blog($id);
                self::clear_total_cache();
            }
            switch_to_blog($legacy);
            if (is_admin()) {
                add_action(
                    'network_admin_notices',
                    array(
                        __CLASS__,
                        'clear_notice',
                    )
                );
            }
        } else {
            self::clear_total_cache();
            if (is_admin()) {
                add_action(
                    'admin_notices',
                    array(
                        __CLASS__,
                        'clear_notice',
                    )
                );
            }
        }
        if (!is_admin()) {
            wp_safe_redirect(
                remove_query_arg(
                    '_cache',
                    wp_get_referer()
                )
            );
            exit();
        }
    }

    public static function clear_notice(){
        if (!is_admin_bar_showing() or !apply_filters('user_can_clear_cache', current_user_can('manage_options'))) {
            return false;
        }
        echo sprintf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html__('The cache has been deleted.', 'cache')
        );
    }

    public static function edit_comment($id){
        if (self::$options['new_comment']) {
            self::clear_total_cache();
        } else {
            self::clear_page_cache_by_post_id(
                get_comment($id)->comment_post_ID
            );
        }
    }

    public static function new_comment($approved, $comment){
        if ($approved === 1) {
            if (self::$options['new_comment']) {
                self::clear_total_cache();
            } else {
                self::clear_page_cache_by_post_id($comment['comment_post_ID']);
            }
        }
        return $approved;
    }

    public static function change_comment($after_status, $before_status, $comment){
        if ($after_status != $before_status) {
            if (self::$options['new_comment']) {
                self::clear_total_cache();
            } else {
                self::clear_page_cache_by_post_id($comment->comment_post_ID);
            }
        }
    }

    public static function register_publish_hooks(){
        $post_types = get_post_types(
            array('public' => true)
        );
        if (empty($post_types)) {
            return;
        }
        foreach ($post_types as $post_type) {
            add_action('publish_' . $post_type, array( __CLASS__, 'publish_post_types' ), 10, 2 );
            add_action( 'publish_future_' . $post_type, array( __CLASS__, 'clear_total_cache' ) );
        }
    }

    public static function publish_post_types($post_ID, $post){
        if (empty($post_ID) or empty($post)) {
            return;
        }
        if (!in_array($post->post_status, array('publish', 'future'))) {
            return;
        }
        if (!isset($_POST['_clear_post_cache_on_update'])) {
            if (self::$options['new_post']) {
                return self::clear_total_cache();
            } else {
                return self::clear_home_page_cache();
            }

        }
        if (!isset($_POST['_cache__status_nonce_' . $post_ID]) or !wp_verify_nonce($_POST['_cache__status_nonce_' . $post_ID], CE_BASE)) {
            return;
        }
        if (!current_user_can('publish_posts')) {
            return;
        }
        $clear_post_cache = (int) $_POST['_clear_post_cache_on_update'];
        update_user_meta(
            get_current_user_id(),
            '_clear_post_cache_on_update',
            $clear_post_cache
        );
        if ($clear_post_cache) {
            self::clear_page_cache_by_post_id($post_ID);
        } else {
            self::clear_total_cache();
        }
    }

    public static function clear_page_cache_by_post_id($post_ID){
        if (!$post_ID = (int) $post_ID) {
            return;
        }
        self::clear_page_cache_by_url(
            get_permalink($post_ID)
        );
    }

    public static function clear_page_cache_by_url($url){
        if (!$url = (string) $url) {
            return;
        }
        call_user_func( array( self::$disk, 'delete_asset'), $url );
    }

    public static function clear_home_page_cache() {
        call_user_func( array( self::$disk, 'clear_home' ) );
    }

    private static function _preg_split($input){
        return (array) preg_split('/,/', $input, -1, PREG_SPLIT_NO_EMPTY);
    }

    private static function _is_index(){
        return basename($_SERVER['SCRIPT_NAME']) != 'index.php';
    }

    private static function _is_mobile(){
        return (strpos(TEMPLATEPATH, 'wptouch') or strpos(TEMPLATEPATH, 'carrington') or strpos(TEMPLATEPATH, 'jetpack') or strpos(TEMPLATEPATH, 'handheld'));
    }

    private static function _is_logged_in(){
        if (is_user_logged_in()) {
            return true;
        }

        if (empty($_COOKIE)) {
            return false;
        }

        foreach ($_COOKIE as $k => $v) {
            if (preg_match('/^(wp-postpass|wordpress_logged_in|comment_author)_/', $k)) {
                return true;
            }
        }
    }

    private static function _bypass_cache(){
        if (apply_filters('bypass_cache', false)) {
            return true;
        }

        if (self::_is_index() or is_search() or is_404() or is_feed() or is_trackback() or is_robots() or is_preview() or post_password_required()) {
            return true;
        }

        if (defined('DONOTCACHEPAGE') && DONOTCACHEPAGE) {
            return true;
        }

        $options = self::$options;

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'GET') {
            return true;
        }

        if (!empty($_GET) && !isset($_GET['utm_source'], $_GET['utm_medium'], $_GET['utm_campaign']) && get_option('permalink_structure')) {
            return true;
        }

        if (self::_is_logged_in()) {
            return true;
        }

        if (self::_is_mobile()) {
            return true;
        }

        if ($options['excl_ids'] && is_singular()) {
            if (in_array($GLOBALS['wp_query']->get_queried_object_id(), self::_preg_split($options['excl_ids']))) {
                return true;
            }
        }

        return false;
    }

    private static function _minify_cache($data)
    {

        if (!self::$options['minify_html']) {
            return $data;
        }

        if (strlen($data) > 700000) {
            return $data;
        }

        $ignore_tags = (array) apply_filters(
            'cache_minify_ignore_tags',
            array(
                'textarea',
                'pre',
            )
        );

        if (self::$options['minify_html'] !== self::MINIFY_HTML_JS) {
            $ignore_tags[] = 'script';
        }

        if (!$ignore_tags) {
            return $data;
        }

        $ignore_regex = implode('|', $ignore_tags);

        $cleaned = preg_replace(
            array(
                '/<!--[^\[><](.*?)-->/s',
                '#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:' . $ignore_regex . ')\b))*+)(?:<(?>' . $ignore_regex . ')\b|\z))#',
            ),
            array(
                '',
                ' ',
            ),
            $data
        );

        if (strlen($cleaned) <= 1) {
            return $data;
        }

        return $cleaned;
    }

    public static function clear_total_cache()
    {

        __Cache_Disk::clear_cache();

        delete_transient('cache_size');
    }

    public static function set_cache($data)
    {

        if (empty($data)) {
            return '';
        }

        call_user_func(
            array(
                self::$disk,
                'store_asset',
            ),
            self::_minify_cache($data)
        );

        return $data;
    }

    public static function handle_cache()
    {

        if (self::_bypass_cache()) {
            return;
        }
        $cached = call_user_func(
            array(
                self::$disk,
                'check_asset',
            )
        );

        if (empty($cached)) {
            ob_start('__Cache::set_cache');
            return;
        }

        $expired = call_user_func(
            array(
                self::$disk,
                'check_expiry',
            )
        );

        if ($expired) {
            ob_start('__Cache::set_cache');
            return;
        }

        call_user_func(
            array(
                self::$disk,
                'get_asset',
            )
        );
    }

    public static function add_clear_dropdown()
    {

        if (empty($GLOBALS['pagenow']) or $GLOBALS['pagenow'] !== 'post.php' or empty($GLOBALS['post']) or !is_object($GLOBALS['post']) or $GLOBALS['post']->post_status !== 'publish') {
            return;
        }

        if (!current_user_can('publish_posts')) {
            return;
        }

        wp_nonce_field(CE_BASE, '_cache__status_nonce_' . $GLOBALS['post']->ID);

        $current_action = (int) get_user_meta(
            get_current_user_id(),
            '_clear_post_cache_on_update',
            true
        );

        $dropdown_options  = '';
        $available_options = array(
            esc_html__('Completely', 'cache'),
            esc_html__('Page specific', 'cache'),
        );

        foreach ($available_options as $key => $value) {
            $dropdown_options .= sprintf(
                '<option value="%1$d" %3$s>%2$s</option>',
                $key,
                $value,
                selected($key, $current_action, false)
            );
        }

        echo sprintf(
            '<div class="misc-pub-section" style="border-top:1px solid #eee">
				<label for="cache_action">
					%1$s: <span id="output-cache-action">%2$s</span>
				</label>
				<a href="#" class="edit-cache-action hide-if-no-js">%3$s</a>

				<div class="hide-if-js">
					<select name="_clear_post_cache_on_update" id="cache_action">
						%4$s
					</select>

					<a href="#" class="save-cache-action hide-if-no-js button">%5$s</a>
	 				<a href="#" class="cancel-cache-action hide-if-no-js button-cancel">%6$s</a>
	 			</div>
			</div>',
            esc_html__('Clear cache', 'cache'),
            $available_options[$current_action],
            esc_html__('Edit'),
            $dropdown_options,
            esc_html__('OK'),
            esc_html__('Cancel')
        );
    }

    public static function add_admin_resources($hook)
    {

        if ($hook !== 'index.php' and $hook !== 'post.php') {
            return;
        }

        $plugin_data = get_plugin_data(BITWIZE_CORE_PLUGIN_FILE);

        switch ($hook) {

            case 'post.php':
                wp_enqueue_script(
                    'cache-post',
                    plugins_url('framework/assets/js/cache.js', BITWIZE_CORE_PLUGIN_FILE),
                    array('jquery'),
                    $plugin_data['Version'],
                    true
                );
                break;

            default:
                break;
        }
    }

    public static function add_settings_page()
    {

        add_options_page(
            'Cache',
            'Cache',
            'manage_options',
            '__cache',
            array(
                __CLASS__,
                'settings_page',
            )
        );
    }

    private static function _minify_select()
    {

        return array(
            self::MINIFY_DISABLED  => esc_html__('Disabled', 'cache'),
            self::MINIFY_HTML_ONLY => 'HTML',
            self::MINIFY_HTML_JS   => 'HTML & Inline JS',
        );
    }

    public static function register_settings()
    {

        register_setting(
            '__cache',
            'cache',
            array(
                __CLASS__,
                'validate_settings',
            )
        );
    }

    public static function validate_settings($data)
    {

        if (empty($data)) {
            return;
        }

        self::clear_total_cache(true);

        return array(
            'expires'     => (int) $data['expires'],
            'new_post'    => (int) (!empty($data['new_post'])),
            'new_comment' => (int) (!empty($data['new_comment'])),
            'webp'        => (int) (!empty($data['webp'])),
            'compress'    => (int) (!empty($data['compress'])),
            'excl_ids'    => (string) sanitize_text_field(@$data['excl_ids']),
            'minify_html' => (int) $data['minify_html'],
        );
    }

    public static function settings_page()
    {
        ?>

		<div class="wrap" id="cache-settings">
			<h2>
				<?php _e("Cache Settings", "cache")?>
			</h2>

			<p><?php $size = self::get_cache_size();
        printf(__("Current cache size: <b>%s</b>", "cache"), (empty($size) ? esc_html__("Empty", "cache") : size_format($size)));?></p>


			<form method="post" action="options.php">
				<?php settings_fields('__cache')?>

				<?php $options = self::_get_options()?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e("Cache Expiry", "cache")?>
						</th>
						<td>
							<fieldset>
								<label for="cache_expires">
									<input type="text" name="cache[expires]" id="cache_expires" value="<?php echo esc_attr($options['expires']) ?>" />
									<p class="description"><?php _e("Cache expiry in hours. An expiry time of 0 means that the cache never expires.", "cache");?></p>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e("Cache Behavior", "cache")?>
						</th>
						<td>
							<fieldset>
								<label for="cache_new_post">
									<input type="checkbox" name="cache[new_post]" id="cache_new_post" value="1" <?php checked('1', $options['new_post']);?> />
									<?php _e("Clear the complete cache if a new post has been published (instead of only the home page cache).", "cache")?>
								</label>

								<br />

								<label for="cache_new_comment">
									<input type="checkbox" name="cache[new_comment]" id="cache_new_comment" value="1" <?php checked('1', $options['new_comment']);?> />
									<?php _e("Clear the complete cache if a new comment has been posted (instead of only the page specific cache).", "cache")?>
								</label>

								<br />

								<label for="cache_compress">
									<input type="checkbox" name="cache[compress]" id="cache_compress" value="1" <?php checked('1', $options['compress']);?> />
									<?php _e("Pre-compression of cached pages. Needs to be disabled if the decoding fails in the web browser.", "cache")?>
								</label>

								<br />

								<label for="cache_webp">
									<input type="checkbox" name="cache[webp]" id="cache_webp" value="1" <?php checked('1', $options['webp']);?> />
									<?php _e("Create an additional cached version for WebP image support.", "cache")?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php _e("Cache Exclusions", "cache")?>
						</th>
						<td>
							<fieldset>
								<label for="cache_excl_ids">
									<input type="text" name="cache[excl_ids]" id="cache_excl_ids" value="<?php echo esc_attr($options['excl_ids']) ?>" />
									<p class="description"><?php _e("Post or Pages IDs separated by a <code>,</code> that should not be cached.", "cache");?></p>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php _e("Cache Minification", "cache")?>
						</th>
						<td>
							<label for="cache_minify_html">
								<select name="cache[minify_html]" id="cache_minify_html">
									<?php foreach (self::_minify_select() as $k => $v) {?>
										<option value="<?php echo esc_attr($k) ?>" <?php selected($options['minify_html'], $k);?>>
											<?php echo esc_html($v) ?>
										</option>
									<?php }?>
								</select>
							</label>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php submit_button()?>
						</th>
						<td>
							<p class="description"><?php _e("Saving these settings will clear the complete cache.", "cache")?></p>
						</td>
					</tr>
				</table>
			</form>
			<p class="description"><?php _e("It is recommended to enable HTTP/2 on your origin server and use a CDN that supports HTTP/2. Avoid domain sharding and concatenation of your assets to benefit from parallelism of HTTP/2.", "cache")?></p>
		</div><?php
	}
}


final class __Cache_Disk
{

    const FILE_HTML      = 'index.html';
    const FILE_GZIP      = 'index.html.gz';
    const FILE_WEBP_HTML = 'index-webp.html';
    const FILE_WEBP_GZIP = 'index-webp.html.gz';

    public static function is_permalink() {
        return get_option('permalink_structure');
    }

    public static function store_asset($data) {

        if (empty($data)) {
            wp_die('Asset is empty.');
        }

        self::_create_files(
            $data
        );

    }

    public static function check_asset() {
        return is_readable(
            self::_file_html()
        );
    }

    public static function check_expiry() {
        $options = __Cache::$options;
        if ($options['expires'] == 0) {
            return false;
        }
        $now             = time();
        $expires_seconds = 3600 * $options['expires'];
        if ((filemtime(self::_file_html()) + $expires_seconds) <= $now) {
            return true;
        }
        return false;

    }

    public static function delete_asset($url) {
        if (empty($url)) {
            wp_die('URL is empty.');
        }
        self::_clear_dir(
            self::_file_path($url)
        );
    }

    public static function clear_cache() {
        self::_clear_dir(
            CE_CACHE_DIR
        );
    }

    public static function clear_home() {
        $path = sprintf(
            '%s%s%s%s',
            CE_CACHE_DIR,
            DIRECTORY_SEPARATOR,
            preg_replace('#^https?://#', '', get_option('siteurl')),
            DIRECTORY_SEPARATOR
        );

        @unlink($path . self::FILE_HTML);
        @unlink($path . self::FILE_GZIP);
        @unlink($path . self::FILE_WEBP_HTML);
        @unlink($path . self::FILE_WEBP_GZIP);
    }

    public static function get_asset() {
        if (function_exists('apache_request_headers')) {
            $headers                = apache_request_headers();
            $http_if_modified_since = (isset($headers['If-Modified-Since'])) ? $headers['If-Modified-Since'] : '';
            $http_accept            = (isset($headers['Accept'])) ? $headers['Accept'] : '';
            $http_accept_encoding   = (isset($headers['Accept-Encoding'])) ? $headers['Accept-Encoding'] : '';
        } else {
            $http_if_modified_since = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : '';
            $http_accept            = (isset($_SERVER['HTTP_ACCEPT'])) ? $_SERVER['HTTP_ACCEPT'] : '';
            $http_accept_encoding   = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
        }

        if ($http_if_modified_since && (strtotime($http_if_modified_since) == filemtime(self::_file_html()))) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', true, 304);
            exit;
        }

        if ($http_accept && (strpos($http_accept, 'webp') !== false)) {
            if (is_readable(self::_file_webp_gzip())) {
                header('Content-Encoding: gzip');
                readfile(self::_file_webp_gzip());
                exit;
            } elseif (is_readable(self::_file_webp_html())) {
                readfile(self::_file_webp_html());
                exit;
            }
        }

        if ($http_accept_encoding && (strpos($http_accept_encoding, 'gzip') !== false) && is_readable(self::_file_gzip())) {
            header('Content-Encoding: gzip');
            readfile(self::_file_gzip());
            exit;
        }

        readfile(self::_file_html());
        exit;
    }

    private static function _cache_signatur() {
        return sprintf(
            "\n\n<!-- %s @ %s",
            'Cache by Bitwize',
            date_i18n(
                'd.m.Y H:i:s',
                current_time('timestamp')
            )
        );
    }

    private static function _create_files($data) {

        if (!wp_mkdir_p(self::_file_path())) {
            wp_die('Unable to create directory.');
        }

        $cache_signature = self::_cache_signatur();

        $options = __Cache::$options;

        self::_create_file(self::_file_html(), $data . $cache_signature . " (html) -->");

        if ($options['compress']) {
            self::_create_file(self::_file_gzip(), gzencode($data . $cache_signature . " (html gzip) -->", 9));
        }

        if ($options['webp']) {
            $converted_data = self::_convert_webp($data);
            self::_create_file(self::_file_webp_html(), $converted_data . $cache_signature . " (webp) -->");

            if ($options['compress']) {
                self::_create_file(self::_file_webp_gzip(), gzencode($converted_data . $cache_signature . " (webp gzip) -->", 9));
            }
        }

    }

    private static function _create_file($file, $data) {
        if (!$handle = @fopen($file, 'wb')) {
            wp_die('Can not write to file.');
        }
        @fwrite($handle, $data);
        fclose($handle);
        clearstatcache();

        $stat  = @stat(dirname($file));
        $perms = $stat['mode'] & 0007777;
        $perms = $perms & 0000666;
        @chmod($file, $perms);
        clearstatcache();
    }

    private static function _clear_dir($dir) {

        $dir = untrailingslashit($dir);

        if (!is_dir($dir)) {
            return;
        }

        $objects = array_diff(
            scandir($dir),
            array('..', '.')
        );

        if (empty($objects)) {
            return;
        }

        foreach ($objects as $object) {
            $object = $dir . DIRECTORY_SEPARATOR . $object;

            if (is_dir($object)) {
                self::_clear_dir($object);
            } else {
                unlink($object);
            }
        }

        @rmdir($dir);

        clearstatcache();
    }

    public static function cache_size($dir = '.') {

        if (!is_dir($dir)) {
            return;
        }

        $objects = array_diff(
            scandir($dir),
            array('..', '.')
        );

        if (empty($objects)) {
            return;
        }

        $size = 0;

        foreach ($objects as $object) {
            $object = $dir . DIRECTORY_SEPARATOR . $object;

            if (is_dir($object)) {
                $size += self::cache_size($object);
            } else {
                $size += filesize($object);
            }
        }

        return $size;
    }

    private static function _file_path($path = null)
    {

        $path = sprintf(
            '%s%s%s%s',
            CE_CACHE_DIR,
            DIRECTORY_SEPARATOR,
            parse_url(
                'http://' . strtolower($_SERVER['HTTP_HOST']),
                PHP_URL_HOST
            ),
            parse_url(
                ($path ? $path : $_SERVER['REQUEST_URI']),
                PHP_URL_PATH
            )
        );

        if (validate_file($path) > 0) {
            wp_die('Path is not valid.');
        }

        return trailingslashit($path);
    }

    private static function _file_html()
    {
        return self::_file_path() . self::FILE_HTML;
    }

    private static function _file_gzip()
    {
        return self::_file_path() . self::FILE_GZIP;
    }

    private static function _file_webp_html()
    {
        return self::_file_path() . self::FILE_WEBP_HTML;
    }

    private static function _file_webp_gzip()
    {
        return self::_file_path() . self::FILE_WEBP_GZIP;
    }

    private static function _convert_webp($data)
    {

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8'));

        $imgs = $dom->getElementsByTagName("img");

        foreach ($imgs as $img) {

            $src      = $img->getAttribute('src');
            $src_webp = self::_convert_webp_src($src);
            if ($src != $src_webp) {
                $img->setAttribute('src', $src_webp);

                $srcset = $img->getAttribute('srcset');
                $img->setAttribute('srcset', self::_convert_webp_srcset($srcset));
            }

        }

        $img_links = $dom->getElementsByTagName("a");

        foreach ($img_links as $img_link) {

            $src      = $img_link->getAttribute('href');
            $src_webp = self::_convert_webp_src($src);
            if ($src != $src_webp) {
                $img_link->setAttribute('href', $src_webp);
            }

        }

        return $dom->saveHtml();

    }

    private static function _convert_webp_src($src)
    {
        if (strpos($src, 'wp-content') !== false) {

            $src_webp = str_replace('.jpg', '.webp', $src);
            $src_webp = str_replace('.png', '.webp', $src_webp);

            $parts         = explode('/wp-content/uploads', $src_webp);
            $relative_path = $parts[1];

            $upload_path = wp_upload_dir();
            $base_dir    = $upload_path['basedir'];

            if (!empty($relative_path) && file_exists($base_dir . $relative_path)) {
                return $src_webp;
            }

        }

        return $src;
    }

    private static function _convert_webp_srcset($srcset)
    {

        $sizes = explode(', ', $srcset);

        for ($i = 0; $i < count($sizes); $i++) {

            if (strpos($sizes[$i], 'wp-content') !== false) {

                $src_webp = str_replace('.jpg', '.webp', $sizes[$i]);
                $src_webp = str_replace('.png', '.webp', $src_webp);

                $sizeParts     = explode(' ', $src_webp);
                $parts         = explode('/wp-content/uploads', $sizeParts[0]);
                $relative_path = $parts[1];

                $upload_path = wp_upload_dir();
                $base_dir    = $upload_path['basedir'];

                if (!empty($relative_path) && file_exists($base_dir . $relative_path)) {
                    $sizes[$i] = $src_webp;
                }

            }

        }

        $srcset = implode(', ', $sizes);

        return $srcset;
    }

}

new __Cache;
