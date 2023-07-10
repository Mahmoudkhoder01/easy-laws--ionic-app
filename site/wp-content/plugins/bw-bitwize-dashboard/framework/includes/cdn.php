<?php

class BWD_CDN {

	public $blog_url     = null;
    public $cdn_url      = null;
    public $enabled      = false;
    // public $dirs         = null;
    public $excludes     = array();
    public $relative     = false;
    public $https        = false;

    public function __construct(){
    	add_action( 'plugins_loaded', array( $this, 'init'));
    }

    public function init() {
        $options = $this->get_options();
        $excludes = array_map('trim', explode(',', $options['excludes']));
        $this->enabled    = $options['enabled'];
        $this->blog_url = get_option('home');
        $this->cdn_url  = $options['url'];
        // $this->dirs     = $options['dirs'];
        $this->excludes = $excludes;
        $this->relative = $options['relative'];
        $this->https    = $options['https'];
        add_action( 'template_redirect', array( $this, 'handle_rewrite_hook' ) );
        add_action( 'admin_init', array( $this, 'register_textdomain' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
    }

    public function register_textdomain() {
        load_plugin_textdomain( 'cdn-enabler', false, 'cdn-enabler/lang' );
    }

    public function get_options() {
        return wp_parse_args(
            get_option('cdn_enabler'),
            array(
                'enabled'  => 0,
                'url'      => get_option('home'),
                // 'dirs'     => 'wp-content,wp-includes',
                'excludes' => '.php',
                'relative' => 1,
                'https'    => 1,
            )
        );
    }

    public function handle_rewrite_hook() {
        if(is_admin()) return;
        if( !$this->enabled ) return;
        if (get_option('home') == $this->cdn_url) return;

        ob_start( array(&$this, 'rewrite') );
    }

    // REWRITE

    protected function exclude_asset(&$asset) {
        foreach ($this->excludes as $exclude) {
            if (!!$exclude && stristr($asset, $exclude) != false) {
                return true;
            }
        }
        return false;
    }

    protected function rewrite_url($asset) {
        if ($this->exclude_asset($asset[0])) return $asset[0];
        $blog_url = $this->blog_url;
        if (!$this->relative || strstr($asset[0], $blog_url)) return str_replace($blog_url, $this->cdn_url, $asset[0]);
        return $this->cdn_url . $asset[0];
    }

    protected function get_dir_scope() {
        return 'wp\-content|sys|wp\-includes|lib';
        // $input = explode(',', $this->dirs);
        // if ($this->dirs == '' || count($input) < 1) return 'wp\-content|wp\-includes';
        // return implode('|', array_map('quotemeta', array_map('trim', $input)));
    }

    public function rewrite($html) {
        if ( !$this->https && bw_is_ssl() ) return $html;
        $dirs     = $this->get_dir_scope();
        $blog_url = quotemeta($this->blog_url);
        $regex_rule = '#(?<=[(\"\'])';
        if ($this->relative) {
            $regex_rule .= '(?:' . $blog_url . ')?';
        } else {
            $regex_rule .= $blog_url;
        }
        $regex_rule .= '/(?:((?:' . $dirs . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';
        $cdn_html = preg_replace_callback($regex_rule, array(&$this, 'rewrite_url'), $html);
        return $cdn_html;
    }

    // SETTINGS

    public function register_settings() {
        register_setting( 'cdn_enabler', 'cdn_enabler', array( $this, 'validate_settings', ) );
    }

    public function validate_settings($data) {
        return array(
            'enabled'    => (int) ($data['enabled']),
            'url'      => esc_url($data['url']),
            // 'dirs'     => esc_attr($data['dirs']),
            'excludes' => esc_attr($data['excludes']),
            'relative' => (int) ($data['relative']),
            'https'    => (int) ($data['https']),
        );
    }

    public function add_settings_page() {
        $page = add_options_page( 'CDN', 'CDN', 'manage_options', 'cdn_enabler', array( $this, 'settings_page', ) );
    }

    public function settings_page() { ?>
		<div class="wrap">
			<h2><?php _e("CDN Enabler Settings", "cdn-enabler");?></h2>

			<form method="post" action="options.php">
				<?php settings_fields('cdn_enabler')?>

				<?php $options = $this->get_options()?>

				<table class="form-table">

                    <tr valign="top">
                        <th scope="row"><?php _e("Enable CDN", "cdn-enabler");?></th>
                        <td>
                            <fieldset>
                                <label for="cdn_enabler_enabled">
                                    <input type="checkbox" name="cdn_enabler[enabled]" id="cdn_enabler_https" value="1" <?php checked(1, $options['enabled'])?> />
                                    <?php _e("Enable CDN?", "cdn-enabler");?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

					<tr valign="top">
						<th scope="row"><?php _e("CDN URL", "cdn-enabler");?></th>
						<td>
							<fieldset>
								<label for="cdn_enabler_url">
									<input type="text" name="cdn_enabler[url]" id="cdn_enabler_url" value="<?php echo $options['url']; ?>" size="64" class="regular-text code" />
								</label>

								<p class="description">
									<?php _e("Enter the CDN URL without trailing", "cdn-enabler");?> <code>/</code>
								</p>
							</fieldset>
						</td>
					</tr>

					<!-- <tr valign="top">
						<th scope="row"><?php //_e("Included Directories", "cdn-enabler");?></th>
						<td>
							<fieldset>
								<label for="cdn_enabler_dirs">
									<input type="text" name="cdn_enabler[dirs]" id="cdn_enabler_dirs" value="<?php //echo $options['dirs']; ?>" size="64" class="regular-text code" />
									<?php //_e("Default: <code>wp-content,wp-includes</code>", "cdn-enabler");?>
								</label>
								<p class="description"><?php //_e("Assets in these directories will be pointed to the CDN URL. Enter the directories separated by", "cdn-enabler");?> <code>,</code></p>
							</fieldset>
						</td>
					</tr> -->

					<tr valign="top">
						<th scope="row"><?php _e("Exclusions", "cdn-enabler");?></th>
						<td>
							<fieldset>
								<label for="cdn_enabler_excludes">
									<input type="text" name="cdn_enabler[excludes]" id="cdn_enabler_excludes" value="<?php echo $options['excludes']; ?>" size="64" class="regular-text code" />
									<?php _e("Default: <code>.php</code>", "cdn-enabler");?>
								</label>
								<p class="description"><?php _e("Enter the exclusions (directories or extensions) separated by", "cdn-enabler");?> <code>,</code></p>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e("Relative Path", "cdn-enabler");?></th>
						<td>
							<fieldset>
								<label for="cdn_enabler_relative">
									<input type="checkbox" name="cdn_enabler[relative]" id="cdn_enabler_relative" value="1" <?php checked(1, $options['relative'])?> />
									<?php _e("Enable CDN for relative paths (default: enabled).", "cdn-enabler");?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e("CDN HTTPS", "cdn-enabler");?></th>
						<td>
							<fieldset>
								<label for="cdn_enabler_https">
									<input type="checkbox" name="cdn_enabler[https]" id="cdn_enabler_https" value="1" <?php checked(1, $options['https'])?> />
									<?php _e("Enable CDN for HTTPS connections (default: disabled).", "cdn-enabler");?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>

				<?php submit_button()?>
			</form>
		</div><?php
	}

}

$GLOBALS['BWD_CDN'] = new BWD_CDN;
