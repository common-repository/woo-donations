<?php
/**
 * Register all functions for the plugin
 */
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Get form setting options
 */
function wdgk_get_wc_donation_setting(){
	$donation_settings = get_option('wdgk_donation_settings');
	return apply_filters('filter_woo_donations_settings', $donation_settings);
}

/**
 * Success message
 */
function wdgk_success_option_msg_wdgk($msg){
	return ' <div class="notice notice-success wdgk-success-msg is-dismissible"><p>'. $msg . '</p></div>';		
}

/**
 * Error message
 */
function wdgk_failure_option_msg_wdgk($msg){
	return '<div class="notice notice-error wdgk-error-msg is-dismissible"><p>' . $msg . '</p></div>';		
}

/**
 * Checks if a product is marked as a donation product
 */
function wdgk_is_donatable($id) {
	$product = "";
	$options = wdgk_get_wc_donation_setting();
	if (isset($options['Product'])) {
		$product = $options['Product'];
	}

	if(is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
		// Get the default language
		$default_language = apply_filters('wpml_default_language', null);
		// Get the original product ID
		$id = apply_filters('wpml_object_id', $id, 'product', true, $default_language);
	}

	return apply_filters('wdgk_is_donatable', get_post_meta($id, '_donatable', true) == 'yes' && $product != $id);
}

/**
 * Add donation product to cart
 */
function wdgk_add_donation_product_to_cart($id, $quantity = 1, $variation_id = '') {
	$found = false;
	$productObj = wc_get_product($id);
	//check if product already in cart
	if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
		
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];

			if(!empty($variation_id)){
				if(isset($values['variation_id']) && $values['variation_id'] == $variation_id) {				
					WC()->cart->remove_cart_item($cart_item_key);
				}
			}
			
			if ( $_product->get_id() == $id ) {
				$found = true;
				WC()->cart->remove_cart_item($cart_item_key);
				if($productObj->is_type('variable')) {
					WC()->cart->add_to_cart( $id, $quantity, $variation_id );
				}else{
					WC()->cart->add_to_cart( $id );
				}
			}
			
		}
		// if product not found, add it
		if ( ! $found )
			if($productObj->is_type('variable')) {
				WC()->cart->add_to_cart( $id, $quantity, $variation_id );
			}else{
				WC()->cart->add_to_cart( $id );
			}
	
	} else {
		// if no products in cart, add it
		if($productObj->is_type('variable')) {
			WC()->cart->add_to_cart( $id, $quantity, $variation_id );
		}else{
			WC()->cart->add_to_cart( $id );
		}
	}
}

/** 
 * Check woocommerce is using high speed order storage or not using
 */
function wdgk_woocommerce_hpos_tables_used() {
	if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return true;
	}

	return false;
}

/** 
 * Internal Style for Donation Form
 */
function wdgk_form_internal_style() {
	$color 				= "";
	$textcolor 			= "";
	$additional_style	= "";
	$options = wdgk_get_wc_donation_setting();

	if (isset($options['Color'])) {
		$color = $options['Color'];
		$additional_style .= '.wdgk_donation_content a.button.wdgk_add_donation { background-color: ' . esc_attr($color) . ' !important; } ';
	}

	if (isset($options['TextColor'])) {
		$textcolor = $options['TextColor'];
		$additional_style .= '.wdgk_donation_content a.button.wdgk_add_donation { color: ' . esc_attr($textcolor) . ' !important; }';
	}

	return $additional_style;
}

/**
 * Invalid Donation Product Message
 */
function wdgk_form_invalid_message($message) {
	return printf('<ul class="woocommerce-error wdgk-invalid-donation-message" id="wdgk-invalid-donation" role="alert"><li>%s</li></ul>', esc_html($message));
}