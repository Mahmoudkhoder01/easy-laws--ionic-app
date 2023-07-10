<?php
class BW_WC_Customizer
{
    public function __construct() {
        //remove generator meta tag
        add_action('plugins_loaded', array($this, 'plugins_loaded'));

        // OPTIMIZE SHOP SCRIPTS
        // add_action( 'wp_enqueue_scripts', array($this, 'manage_styles'), 99 );

        // disable addons
        add_action( 'admin_menu', array($this, 'disable_wc_addons'), 999 );

        // Make Postal code Not required
        add_filter( 'woocommerce_default_address_fields', array($this, 'override_default_address_fields') );

        // Hook into the checkout fields (shipping & billing)
        // add_filter( 'woocommerce_checkout_fields' , array($this, 'custom_override_checkout_fields' ));

        // Hook into the default fields
        // add_filter( 'woocommerce_default_address_fields' , array($this, 'custom_override_default_address_fields' ));

        // ADD LEBANON STATES
        add_filter( 'woocommerce_states', array($this, 'extra_states') );
    }

    public function plugins_loaded(){
        remove_action( 'wp_head', array($GLOBALS['woocommerce'], 'generator') );
    }

    public function manage_styles() {

        if( !is_woocommerce() && !is_cart() && !is_checkout() ) {
            wp_dequeue_style( 'woocommerce_frontend_styles' );
            wp_dequeue_style( 'woocommerce_fancybox_styles' );
            wp_dequeue_style( 'woocommerce_chosen_styles' );
            wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
            wp_dequeue_script( 'wc_price_slider' );
            wp_dequeue_script( 'wc-single-product' );
            wp_dequeue_script( 'wc-add-to-cart' );
            wp_dequeue_script( 'wc-cart-fragments' );
            wp_dequeue_script( 'wc-checkout' );
            wp_dequeue_script( 'wc-add-to-cart-variation' );
            wp_dequeue_script( 'wc-single-product' );
            wp_dequeue_script( 'wc-cart' );
            wp_dequeue_script( 'wc-chosen' );
            wp_dequeue_script( 'woocommerce' );
            wp_dequeue_script( 'prettyPhoto' );
            wp_dequeue_script( 'prettyPhoto-init' );
            wp_dequeue_script( 'jquery-blockui' );
            wp_dequeue_script( 'jquery-placeholder' );
            wp_dequeue_script( 'fancybox' );
            // wp_dequeue_script( 'jqueryui' );

        }
    }
    // DISABLE PAGES

    public function disable_wc_addons() {
        remove_submenu_page( 'woocommerce', 'wc-addons' );
        if( !current_user_can( 'can_bitwize' ) ) {
            remove_submenu_page( 'woocommerce', 'wc-status' );
        }
    }

    public function override_default_address_fields( $address_fields ) {
        $address_fields['postcode']['required'] = false;
        return $address_fields;
    }

    public function custom_override_checkout_fields( $fields ) {
        unset( $fields['billing']['billing_postcode'] );
        unset( $fields['shipping']['shipping_postcode'] );

        return $fields;
    }

    public function custom_override_default_address_fields( $address_fields ) {
        unset( $address_fields['postcode'] );
        return $address_fields;
    }

    public function extra_states( $states ) {
        $states['LB'] = array('BEIRUT' => 'Beirut', 'BEKAA' => 'Bekaa', 'MOUNT' => 'Mount Lebanon', 'NABATIYEH' => 'Nabatiyeh', 'NORTH' => 'North Lebanon', 'SOUTH' => 'South Lebanon',);
        return apply_filters( 'store_extra_states', $states );
    }
}
return new BW_WC_Customizer;
