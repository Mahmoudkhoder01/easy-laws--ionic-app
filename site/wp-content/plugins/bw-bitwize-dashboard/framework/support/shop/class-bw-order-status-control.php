<?php


if ( ! defined( 'ABSPATH' ) ) exit;

class BW_EC_Order_Status_Control {
	protected static $instance;
	const TEXT_DOMAIN = 'bw-ec-order-status-control';

	public function __construct() {
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'handle_payment_complete_order_status' ), 10, 2 );
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_filter( 'woocommerce_general_settings', array( $this, 'add_global_settings' ) );
		}
		add_action('admin_init', array($this, 'install'));
	}

	public function handle_payment_complete_order_status( $order_status, $order_id ) {
		$order = wc_get_order( $order_id );
		$order_type_to_complete = get_option( 'order_status_control_auto_complete_orders' );

		if ( 'all' == $order_type_to_complete ) {
			$order_status = 'completed';
		} elseif ( 'virtual' == $order_type_to_complete ) {
			if ( 'processing' == $order_status &&
			  ( in_array( $order->get_status(), array( 'on-hold', 'pending', 'failed' ) ) ) ) {
				$virtual_order = false;
				$order_items = $order->get_items();
				if ( count( $order_items ) > 0 ) {
					foreach( $order_items as $item ) {
						$product = $order->get_product_from_item( $item );
						if ( ! $product->is_virtual() ) {
							$virtual_order = false;
							break;
						} else {
							$virtual_order = true;
						}
					}
				}
				if ( $virtual_order ) {
					$order_status = 'completed';
				}
			}
		}
		return $order_status;
	}

	public function add_global_settings( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $setting ) {
			$updated_settings[] = $setting;
			if ( isset( $setting['id'] ) && 'woocommerce_demo_store' === $setting['id'] ) {
				$updated_settings = array_merge( $updated_settings, $this->get_global_settings() );
			}
		}
		return $updated_settings;
	}

	public function get_global_settings() {
		return apply_filters( 'bw_ec_order_status_control_global_settings', array(
			array(
				'title'    => __( 'Orders to Auto-Complete', self::TEXT_DOMAIN ),
				'desc_tip' => __( 'Select which types of orders should be changed to completed when payment is received.', self::TEXT_DOMAIN ),
				'id'       => 'order_status_control_auto_complete_orders',
				'default'  => 'none',
				'type'     => 'select',
				'options'  => array(
					'none'    => __( 'None', self::TEXT_DOMAIN ),
					'all'     => __( 'All Orders', self::TEXT_DOMAIN ),
					'virtual' => __( 'Virtual Orders', self::TEXT_DOMAIN ),
				),
			),

		) );
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) self::$instance = new self();
		return self::$instance;
	}

	public function install() {
		foreach ( $this->get_global_settings() as $setting ) {
			if ( isset( $setting['default'] ) ) {
				add_option( $setting['id'], $setting['default'] );
			}
		}
	}
}


function bw_ec_order_status_control() {
	return BW_EC_Order_Status_Control::instance();
}

$GLOBALS['bw_ec_order_status_control'] = bw_ec_order_status_control();

