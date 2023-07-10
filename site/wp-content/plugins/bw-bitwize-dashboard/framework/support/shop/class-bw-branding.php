<?php

class SS_WC_Branding
{
    var $branding_name;
    var $branding_icon;

    public function __construct() {
        global $woocommerce;
        $this->branding_name = apply_filters( 'ss_wc_branding', 'Store' );
        $this->branding_icon = apply_filters( 'ss_wc_branding_icon', plugins_url( 'ss-icon.png', __FILE__ ) );
        // Rebrand the admin menu.
        add_action( 'admin_menu', array($this, 'rebrand_admin_menu'), 10 );
        add_action( 'admin_init', array($this, 'rebrand_admin_settings'), 10 );
        // Init late functions
        add_action( 'init', array($this, 'init') );
        // Get text
        if( defined( 'WPLANG' ) && WPLANG ) {
            add_action( 'init', array($this, 'replace_woocommerce_text'), 10 );
        }
        else {
            add_filter( 'gettext', array($this, 'replace_woocommerce_text_gettext'), 10, 3 );
            add_filter( 'ngettext', array($this, 'replace_woocommerce_text_gettext'), 10, 3 );
        }

        add_filter( 'all_plugins', array($this, 'all_plugins') );
        // Icons
        add_action( 'admin_head', array($this, 'icons') );
        // New shortcode names, without woocommerce_ prefix
        add_shortcode( 'cart', 'get_woocommerce_cart' );
        add_shortcode( 'checkout', 'get_woocommerce_checkout' );
        add_shortcode( 'order_tracking', 'get_woocommerce_order_tracking' );
        add_shortcode( 'my_account', 'get_woocommerce_my_account' );
        add_shortcode( 'edit_address', 'get_woocommerce_edit_address' );
        add_shortcode( 'change_password', 'get_woocommerce_change_password' );
        add_shortcode( 'view_order', 'get_woocommerce_view_order' );
        add_shortcode( 'pay', 'get_woocommerce_pay' );
        add_shortcode( 'thankyou', 'get_woocommerce_thankyou' );
        // add_shortcode( 'messages', 'messages_shortcode' );

        // Screen IDs
        add_filter( 'woocommerce_reports_screen_id', array($this, 'reports_screen_id') );
        add_filter( 'woocommerce_subscriptions_screen_id', array($this, 'subscriptions_screen_id') );
        add_filter( 'woocommerce_screen_ids', array($this, 'screen_ids'), 50 );

        add_filter('parse_query', array($this, 'exclude_pages_from_admin' ));
        add_filter('jump_menu_disabled', array($this, 'exclude_pages_from_jump_menu' ));
        add_action( 'save_post', array($this, 'force_fix_woo_sales_save'), 10, 3 );

        add_action('admin_init', function(){
            global $post;
            if($post && isset($post->post_type) && 'product' == $post->post_type){
                add_filter( 'wp_default_editor', function(){ return 'html'; } );
            }
        });
    }

    public function force_fix_woo_sales_save( $post_id, $post, $update ) {

        $slug = 'product';

        if ( $slug != $post->post_type ) {
            return;
        }

        if(!isset($_REQUEST['action'])) return;

        if( $_REQUEST['action'] == 'edit' ){
            return;
        }

        if( $_REQUEST['action'] == 'inline-save' || $_REQUEST['action'] == 'editpost' ){

            // echo '<pre>'; print_r($_REQUEST); echo '</pre>'; die();

            if ( !empty( $_REQUEST['_sale_price'] ) && !empty( $_REQUEST['_regular_price'] ) ) {
                update_post_meta( $post_id, '_price', sanitize_text_field( $_REQUEST['_sale_price'] ) );
                update_post_meta( $post_id, '_sale_price', sanitize_text_field( $_REQUEST['_sale_price'] ) );
                update_post_meta( $post_id, '_regular_price', sanitize_text_field( $_REQUEST['_regular_price'] ) );
            }else if( empty( $_REQUEST['_sale_price'] ) && !empty( $_REQUEST['_regular_price'] ) ){
                update_post_meta( $post_id, '_price', sanitize_text_field( $_REQUEST['_regular_price'] ) );
                update_post_meta( $post_id, '_sale_price', '' );
            }else if( empty( $_REQUEST['_sale_price'] ) && empty( $_REQUEST['_regular_price'] ) ){
                    update_post_meta( $post_id, '_sale_price', '' );
                    update_post_meta( $post_id, '_regular_price', '' );
                    update_post_meta( $post_id, '_price', '' );
            }else if( !empty( $_REQUEST['_sale_price'] ) && empty( $_REQUEST['_regular_price'] ) ){
                    update_post_meta( $post_id, '_sale_price', '' );
                    update_post_meta( $post_id, '_regular_price', '' );
                    update_post_meta( $post_id, '_price', '' );
            }

            if(empty($_REQUEST['excerpt']) || empty($_REQUEST['content'])){
                global $wpdb;
                $args = array();
                if(empty($_REQUEST['excerpt'])) $args['post_excerpt'] = '';
                if(empty($_REQUEST['content'])) $args['post_content'] = '';
                $wpdb->update( $wpdb->posts, $args, array('ID' => $post_id) );
            }
        }
    }

    public function get_pages_array(){
        $o = array();
        $o[] = get_option( 'woocommerce_shop_page_id' );
        $o[] = get_option( 'woocommerce_cart_page_id' );
        $o[] = get_option( 'woocommerce_checkout_page_id' );
        $o[] = get_option( 'woocommerce_pay_page_id' );
        $o[] = get_option( 'woocommerce_thanks_page_id' );
        $o[] = get_option( 'woocommerce_myaccount_page_id' );
        $o[] = get_option( 'woocommerce_edit_address_page_id' );
        $o[] = get_option( 'woocommerce_view_order_page_id' );
        // $o[] = get_option( 'woocommerce_terms_page_id' );

        $o[] = get_option( 'yith_wcwl_wishlist_page_id' );
        return apply_filters('bw_shop_excluded_admin_pages', $o);
    }

    public function exclude_pages_from_admin($query){
		if ( ! is_admin() ) return $query;

		global $pagenow, $post_type;


        if( !current_user_can( 'can_bitwize' ) ){
    		if ( $pagenow == 'edit.php' && $post_type == 'page' ){
                $r = array();
                $p = $this->get_pages_array();
                if($p){
                    foreach($p as $v){
                        if($v) $r[] = $v;
                    }
                }

    			$query->query_vars['post__not_in'] = $r ;
    		}
        }
	}

    public function exclude_pages_from_jump_menu($query){
		if ( ! is_admin() ) return;
		if(!is_array($query)) $query = array($query);
        $p = $this->get_pages_array();
        if($p){
            foreach($p as $v){
                if($v) $query[] = $v;
            }
        }
		return $query;
	}

    public function reports_screen_id() {
        return sanitize_title_with_dashes( $this->branding_name ) . '_page_woocommerce_reports';
    }

    public function subscriptions_screen_id() {
        return sanitize_title_with_dashes( $this->branding_name ) . '_page_subscriptions';
    }

    public function screen_ids( $screen_ids ) {
        $screen_ids[] = sanitize_title_with_dashes( $this->branding_name ) . '_page_woocommerce_settings';

        foreach( $screen_ids as $screen_id )$screen_ids[] = str_replace( 'woocommerce_page_', sanitize_title_with_dashes( $this->branding_name ) . '_page_', $screen_id );

        return $screen_ids;
    }

    public function init() {
        global $woocommerce;
        // Remove generator
        remove_action( 'wp_head', array($woocommerce, 'generator') );
        // CSS
        $print_css_on = array('toplevel_page_' . sanitize_title_with_dashes( strtolower( $this->branding_name ) ), sanitize_title_with_dashes( strtolower( $this->branding_name ) ) . '_page_woocommerce_reports',);

        foreach( $print_css_on as $page )add_action( 'admin_print_styles-' . $page, 'woocommerce_admin_css' );
    }
    /**
     * Replaces a string in the internationalisation table with a custom value.
     */
    public function replace_woocommerce_text() {
        global $l10n;

        foreach( $l10n as $plugin_key => $plugin ) {
            foreach( $plugin->entries as $entry_key => $entries ) {
                foreach( $entries->translations as $key => $value ) {
                    if( stristr( $value, 'woocommerce' ) ) {
                        $l10n[$plugin_key]->entries[$entry_key]->translations[$key] = str_ireplace( 'woocommerce', $this->branding_name, $value );
                    }
                }
            }
        }
    }
    /**
     * Replace a string with gettext
     */
    public function replace_woocommerce_text_gettext( $translated ) {
        return str_ireplace( 'woocommerce', $this->branding_name, $translated );
    }

    public function all_plugins( $plugins ) {
        foreach( $plugins as $key => $value ) {
            $plugins[$key]['Name'] = str_replace( array('WooCommerce', 'woocommerce', 'Woocommerce'), $this->branding_name, $plugins[$key]['Name'] );
            $plugins[$key]['Description'] = str_replace( array('WooCommerce', 'woocommerce', 'Woocommerce'), $this->branding_name, $plugins[$key]['Description'] );
        }
        return $plugins;
    }

    public function icons() {
?>
		<style type="text/css">
				/*span.mce_woocommerce_shortcodes_button {
					background-image: url("<?php
        echo esc_attr( $this->branding_icon ); ?>") !important;
				}*/
				span.mce_woocommerce_shortcodes_button:before {
					display: none !important;
				}
				#adminmenu #toplevel_page_woocommerce .menu-icon-generic div.wp-menu-image:before{font-family:dashicons!important; content: "\f513"!important; font-size:20px!important;}

				/*#wpwrap #toplevel_page_woocommerce .wp-menu-image { background-image: none !important; }*/
				/*#wpwrap #toplevel_page_woocommerce .wp-menu-image img { display: block !important; width: 20px !important; height: 20px !important; margin-left: 8px !important; padding-top: 7px !important; }*/

				#content_woocommerce_shortcodes_button img { display: none; }
				#content_woocommerce_shortcodes_button { background-repeat: no-repeat; background-position: center;  }
				.wc-badge{display: none !important;}
				.dashboard_page_wc-about .about-wrap p.woocommerce-actions{display: none !important;}
				.dashboard_page_wc-about .about-wrap h2.nav-tab-wrapper{display: none !important;}
		</style>
	<?php
    }

    public function strpos( $haystack, $needles = array(), $offset = 0 ) {
        $chr = array();
        foreach( $needles as $needle ) {
            $res = strpos( $haystack, $needle, $offset );
            if( $res !== false )$chr[$needle] = $res;
        }
        if( empty( $chr ) )return false;
        return min( $chr );
    }

    public function rebrand_admin_menu() {
        global $menu;

        if( is_array( $menu ) ) {
            foreach( $menu as $k => $v ) {
                if( $v[0] == 'WooCommerce' || $v[0] == $this->branding_name ) {
                    if( $this->branding_name != '' ) {
                        $menu[$k][0] = $this->branding_name;
                    }
                    // if ( $this->branding_icon != '' ) { $menu[$k][6] = $this->branding_icon; }
                    break;
                }
            }
        }
    } // End rebrand_admin_menu()

    public function rebrand_admin_settings() {

        $email_footer = get_option( 'woocommerce_email_footer_text' );
        $needle = array(__( 'Powered by ' . $this->branding_name, 'woocommerce' ), 'Powered by WooCommerce',);
        $pos = $this->strpos( $email_footer, $needle );
        if( $pos !== FALSE ) {
            $new_email_footer = get_bloginfo( 'title' ) . ' - ' . __( 'By SellandSell.com', 'woocommerce' );
            update_option( 'woocommerce_email_footer_text', $new_email_footer );
        }

        $tabs = array('general', 'page', 'catalog', 'inventory', 'shipping', 'tax', 'email', 'integration');

        foreach( $tabs as $k => $v ) {
            add_filter( 'woocommerce_' . $v . '_settings', array($this, 'replace_brand_name'), 10 );
        }
    } // End rebrand_admin_settings()

    public function replace_brand_name( $fields ) {
        if( $this->branding_name != '' && strtolower( $this->branding_name ) != 'woocommerce' ) {
            foreach( $fields as $k => $v ) {
                if( isset( $v['desc'] ) ) {
                    $fields[$k]['desc'] = str_replace( 'WooCommerce', $this->branding_name, $fields[$k]['desc'] );
                }
                if( isset( $v['name'] ) ) {
                    $fields[$k]['name'] = str_replace( 'WooCommerce', $this->branding_name, $fields[$k]['name'] );
                }
            }
        }
        return $fields;
    } // End replace_brand_name()


} // End Class

return new SS_WC_Branding();
