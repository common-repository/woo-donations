<?php
/*
Plugin Name: Woo Donations
Description: Woo Donation is a plugin that is used to collect donations on your websites based on Woocommerce. You can add donation functionality in your site to ask your visitors/users community for financial support for the charity or non-profit programs, products, and organisation.
Author: Geek Code Lab
Version: 4.4.3
Author URI: https://geekcodelab.com/
WC tested up to: 9.2.3
Requires Plugins: woocommerce
Text Domain : woo-donations
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WDGK_BUILD', '4.4.3' );

if (!defined('ABSPATH')) exit;

if(!defined("WDGK_PLUGIN_DIR_PATH"))
	define("WDGK_PLUGIN_DIR_PATH",plugin_dir_path(__FILE__));	

if(!defined('WDGK_PLUGIN_URL'))
	define( 'WDGK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if(!defined('WDGK_PLUGIN_PATH'))
	define( 'WDGK_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

function activate_woo_donations() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-donations-activator.php';
	Woo_Donations_Activator::activate();
}

register_activation_hook( __FILE__, 'activate_woo_donations' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woo-donations.php';

/**
 * Settings links when plugin is active
 */
function wdgk_plugin_add_settings_link($links) {
	$support_link = '<a href="https://geekcodelab.com/contact/"  target="_blank" >' . __('Support','woo-donations') . '</a>';
	array_unshift($links, $support_link);

	$doc_link = '<a href="https://geekcodelab.com/wordpress-plugins/woo-donation-pro/"  target="_blank" style="color:#46b450;font-weight: 600;">' . __('Premium Upgrade','woo-donations') . '</a>';
	array_unshift($links, $doc_link);

	$pro_link = '<a href="https://geekcodelab.com/wordpress-plugins/woo-donations/"  target="_blank">' . __('View Doc','woo-donations') . '</a>';
	array_unshift($links, $pro_link);

	$settings_link = '<a href="admin.php?page=wdgk-donation-page">' . __('Settings','woo-donations') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_{$plugin}", 'wdgk_plugin_add_settings_link');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_donations() {

	$plugin = new Woo_Donations();
	$plugin->run();

}
run_woo_donations();

/**
 * WPML - Change product id on diffrent language page for WPML translation
 */
add_filter('filter_woo_donations_settings','wdgk_donation_settings_wpml_support');
function wdgk_donation_settings_wpml_support( $options ) {

	if(defined('ICL_SITEPRESS_VERSION')) {

		if(isset($options) && !empty($options)) {
			if(isset($options['Product']) && !empty($options['Product'])) {
				// Get current language - WPML
				$current_language= apply_filters( 'wpml_current_language', NULL );

				// Get translation ID - WPML
				$options['Product'] = apply_filters( 'wpml_object_id', intval( $options['Product'] ), 'product', true, $current_language );
			}
		}
	}

	return $options;
}

/** Add cron schedule */
add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['wdgk_every_one_minute'] = array(
		'interval' => 180,
		'display' => __( 'Every Three Minute', 'woo-donations' )
	);
	return $schedules;
 } );

/** Set order synchronization on wp init */
add_action( 'init', 'wdgk_update_order_flag_init' );
function wdgk_update_order_flag_init() {
	$wdgk_set_order_flag_status = get_option( 'wdgk_set_order_flag_status' );
	if(!$wdgk_set_order_flag_status) {
		if (! wp_next_scheduled ( 'wdgk_update_order_flag_action' )) {
			wp_schedule_event( time(), 'wdgk_every_one_minute', 'wdgk_update_order_flag_action' );
		}
	}else{
		if (wp_next_scheduled ( 'wdgk_update_order_flag_action' )) {
			wp_clear_scheduled_hook( 'wdgk_update_order_flag_action' );
		}
	}
}

/** Cron schedule which fires during sync orders - every one minute */
add_action( 'wdgk_update_order_flag_action', 'wdgk_do_this_every_five_minute' );
function wdgk_do_this_every_five_minute() {
	$wdgk_set_order_flag_status = get_option( 'wdgk_set_order_flag_status' );

	if(!$wdgk_set_order_flag_status) {
		global $wpdb;
		$settings				= wdgk_get_wc_donation_setting();
		$donation_product_id 	= $settings['Product'];

		$wdgk_set_order_flag_process = get_option( 'wdgk_set_order_flag_process' );
		if(!$wdgk_set_order_flag_process) {
			$wdgk_set_order_flag_process['status'] = 'In progress';
			$wdgk_set_order_flag_process['start'] = 0;
		}

		$interval = 100;
		$statuses = 'trash';
		$start = $wdgk_set_order_flag_process['start'];
		$status = $wdgk_set_order_flag_process['status'];
		
		if( wdgk_woocommerce_hpos_tables_used() ) {
			$sql = "SELECT *,order_items.order_id as order_id
			FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->prefix}wc_orders AS orders ON order_items.order_id = orders.id
			WHERE orders.type = 'shop_order'
			AND orders.status != '".$statuses."'
			AND order_items.order_item_type = 'line_item'
			AND order_item_meta.meta_key = '_product_id'
			AND order_item_meta.meta_value = $donation_product_id LIMIT" . " $start,$interval";
		}else{
			$sql = "SELECT *,order_items.order_id as order_id
			FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			WHERE posts.post_type = 'shop_order'
			AND posts.post_status != '".$statuses."'
			AND order_items.order_item_type = 'line_item'
			AND order_item_meta.meta_key = '_product_id'
			AND order_item_meta.meta_value = $donation_product_id LIMIT" . " $start,$interval";
		}

		$donation_order_result = $wpdb->get_results( $sql, 'ARRAY_A' );
		
		if(count($donation_order_result) != 0) {
			foreach($donation_order_result as $key => $item){
				$order = wc_get_order($item['order_id']);

				$order->update_meta_data( 'wdgk_donation_order_flag', $donation_product_id );
				$order->save();
			}
			update_option( 'wdgk_set_order_flag_process', array("status"=>"In progress","start"=>intval($start)+$interval));
		}else{
			// blank
			$clear_schedule_hook = true;
			update_option('wdgk_set_order_flag_process',array("status"=>"Complete","start"=>intval($start)));
		}

		if(isset($clear_schedule_hook)) {
			wp_clear_scheduled_hook( 'wdgk_update_order_flag_action' );
			update_option( 'wdgk_set_order_flag_status',1 );
		}
	}
}

/** Register gutenberg block for Woo donations form */
add_action( 'init', 'wdgk_wp_donation_block' );
function wdgk_wp_donation_block() {
	if ( in_array( 'elementor/elementor.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		require_once WDGK_PLUGIN_DIR_PATH . 'public/elementor-widget/elementor-addon.php';
	}

	/* Woo Donation Form Block */
    register_block_type( __DIR__ . '/build' );
}

function wdgk_gutenberg_render_callback( $attributes ) {
	ob_start();

	$product_id = (isset($attributes['product_id']) && !empty($attributes['product_id'])) ? $attributes['product_id'] : '';

	$donation_form_html = "";
	$additional_style = wdgk_form_internal_style();

	if($additional_style != "") {
		$donation_form_html .= '<style>'.$additional_style.'</style>';
	}

	$donation_form_html .= do_shortcode('[wdgk_donation product_id="'.$product_id.'"]');
	_e( $donation_form_html );

	return ob_get_clean();
}

/** Admin notice for order sync progress */
add_action( 'admin_notices', 'wdgk_sync_donation_orders_admin_notice' );
function wdgk_sync_donation_orders_admin_notice() {
	$wdgk_set_order_flag_status = get_option( 'wdgk_set_order_flag_status' );
	
	if(!$wdgk_set_order_flag_status) {
		$currentScreen = get_current_screen();
		if(isset($currentScreen->id) && $currentScreen->id == 'woocommerce_page_wdgk-donation-page') {

			$class = 'notice notice-info';			
			printf( '<div class="%1$s"><p>✩ %2$s - <strong>%3$s</strong></p></div>', esc_attr( $class ), __('Database synchronization for donation orders is currently in progress.', 'woo-donations'), __('Woo Donations','woo-donations') );
		}
	}
}

/**
 * Added HPOS support for woocommerce
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
