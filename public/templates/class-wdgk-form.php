<?php
global $woocommerce;

$product_id 		= "";
$text 				= __("Add Donation","woo-donations");
$note 				= "";
$note_html 			= "";
$donation_price 	= "";
$donation_p_id 	    = "";
$donation_note 		= "";
$product_form 		= false;
$wpml_active 		= false;
$form_title			= __("Donation","woo-donations");
$amount_placeholder	= "Ex.100";
$note_placeholder	= "Note";
$invalid_donation_message = "";

$options = wdgk_get_wc_donation_setting();
$attr_product_id = (isset($value['product_id']) && !empty($value['product_id'])) ? $value['product_id'] : "";
$attr_form_title = (isset($value['form_title']) && $value['form_title'] == 'false') ? $value['form_title'] : "";

if(!empty($attr_product_id)) {
    $is_donatable = wdgk_is_donatable($attr_product_id);
    if($is_donatable) {
        $product_form = true;
    }
}

if($product_form) {
    if(is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
        $wpml_active = true;
		// Get the default language
		$default_language = apply_filters('wpml_default_language', null);
		// Get the original product ID
		$product_id = apply_filters('wpml_object_id', $attr_product_id, 'product', true, $default_language);
	}else{
        $product_id = $attr_product_id;
    }
    
    $wdgk_btntext = get_post_meta($product_id, 'wdgk-settings[wdgk_btntext]', true);
    if(isset($wdgk_btntext) && !empty($wdgk_btntext))   $text = $wdgk_btntext;

    $wdgk_add_note = get_post_meta($product_id, 'wdgk-settings[wdgk_add_note]', true);
    if(isset($wdgk_add_note))       $note = $wdgk_add_note;

    $wdgk_title = get_post_meta($product_id, 'wdgk-settings[wdgk_title]', true);
    if(isset($wdgk_title))          $form_title = $wdgk_title;

    $wdgk_amt_place = get_post_meta($product_id, 'wdgk-settings[wdgk_amt_place]', true);
    if(isset($wdgk_amt_place))      $amount_placeholder = $wdgk_amt_place;

    $wdgk_note_place = get_post_meta($product_id, 'wdgk-settings[wdgk_note_place]', true);
    if(isset($wdgk_note_place))     $note_placeholder = $wdgk_note_place;
}else{
    if(isset($options['Product']))      $product_id = $options['Product'];
    if (isset($options['Text']))        $text = $options['Text'];
    if (isset($options['Note']))        $note = $options['Note'];
    if(isset($options['Formtitle']) )   $form_title = $options['Formtitle'];
    if(isset($options['AmtPlaceholder']))   $amount_placeholder = $options['AmtPlaceholder'];
    if(isset($options['Noteplaceholder']))  $note_placeholder = $options['Noteplaceholder'];
}

// When product is not selected as donation
if(empty($product_id)) return;

$post_status = get_post_status($product_id);
if ($post_status != 'publish'){
    echo '<div class="wdgk_error_front">Donation product not found!</div>';
    return;
}

$product = wc_get_product($product_id);

if (!$product || !is_a($product, 'WC_Product')) {
    return wdgk_form_invalid_message(__('Invalid project ID: This project is unknown.', 'woo-donations'));
} else if (!is_a($product, 'WC_Product_Grouped') && !$product->is_purchasable()) {
    return wdgk_form_invalid_message(__('Currently you can not donate to this project.', 'woo-donations'));
} else if (!$product->is_in_stock()) {
    return wdgk_form_invalid_message(__('This project is currently not available.', 'woo-donations'));
}

$has_child = is_a($product, 'WC_Product_Variable') && $product->has_child();
//enqueue woocommerce variation js
if ($has_child) {
    wp_enqueue_script('wc-add-to-cart-variation');
    $current_product = ($wpml_active) ? wc_get_product($attr_product_id) : $product;

    $get_variations         = count($current_product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $current_product);
    $available_variations   = $get_variations ? $current_product->get_available_variations() : false;
    $attributes             = $current_product->get_variation_attributes();
    $attribute_keys         = array_keys($attributes);
    $variations_json        = wp_json_encode($available_variations);
    $variations_attr        = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);
    $selected_attributes    = $current_product->get_default_attributes();
    $get_variations         = count($current_product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $current_product);
}

if($product->is_type('simple')) {
    if(wc()->cart) {
        $cart_count = is_object($woocommerce->cart) ? $woocommerce->cart->get_cart_contents_count() : '';
        if ($cart_count != 0) {
            $cartitems = $woocommerce->cart->get_cart();
            if (!empty($cartitems) && isset($cartitems)) {
                foreach ($cartitems as $item => $values) {                
                    $item_id =  $values['product_id'];
                    if($product_form) {
                        if($product_id == $item_id) {
                            $product_display_price_key = sprintf('wdgk_product_display_price:%s',$product_id);
                            if(array_key_exists($product_display_price_key,$_COOKIE)) {
                                if(isset($_COOKIE[$product_display_price_key])) {
                                    $donation_price = $_COOKIE[$product_display_price_key];
                                }else{
                                    if(isset($values['donation_price']))    $donation_price = $values['donation_price'];
                                }
                                if(isset($values['donation_note'])) $donation_note = str_replace("<br />","\n",$values['donation_note']);
                            }
                        }
                    }else{
                        if ($item_id == $product_id) {
                            $donation_price = isset($_COOKIE['wdgk_product_display_price']) ? $_COOKIE['wdgk_product_display_price'] : $values['donation_price'];
                            if(isset($values['donation_note'])) $donation_note = str_replace("<br />","\n",$values['donation_note']);
                        }
                    }
                }
            }
        }
    }

    if(!empty($donation_price))	{
        $decimal_separator = wc_get_price_decimal_separator();
        $thousand_separator = wc_get_price_thousand_separator();
        $price_decimals = wc_get_price_decimals();
        if(!is_numeric($donation_price)) {                
            $donation_price = str_replace( $decimal_separator, '.', $donation_price );
        }
        $donation_price = number_format($donation_price,$price_decimals,$decimal_separator,$thousand_separator);
    }
}

if (!empty($product_id) && $note == 'on') {
    $note_html = sprintf( '<textarea id="w3mission" rows="3" cols="20" placeholder="%s" name="donation_note" class="donation_note">%s</textarea>', esc_attr(wp_unslash($note_placeholder)), esc_textarea(wp_unslash($donation_note)) );
}

$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();

if( is_checkout() ){
    $redirect_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : $woocommerce->cart->get_checkout_url();
}else{
    $redirect_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
}

$ajax_url = admin_url('admin-ajax.php');
$current_cur = get_woocommerce_currency();
$cur_syambols = get_woocommerce_currency_symbols();

$fn_product_id = ($wpml_active) ? $attr_product_id : $product_id;
?>
<?php if ($has_child): ?>
    <form id="wdgk_variation_submit" class="variations_form cart wdgk-donation-form" method="post" action="<?php echo esc_url($cart_url); ?>"
    autocomplete="off" enctype='multipart/form-data' data-product_id="<?php echo intval($product_id); ?>"
    data-product_variations="<?php esc_attr_e($variations_attr); ?>">
<?php endif; ?>
<div class="wdgk_donation_content">
    <?php 
    if($attr_form_title != 'false' && !empty($form_title)) { ?>
        <h3><?php esc_attr_e(wp_unslash($form_title)) ?></h3>
        <?php
    } ?>

    <div class="wdgk_display_option"> 
        <span><?php esc_html_e($cur_syambols[$current_cur]); ?></span>
        <input type="text" name="donation-price" class="wdgk_donation" placeholder="<?php echo esc_attr(wp_unslash($amount_placeholder)) ?>" value="<?php echo esc_attr($donation_price); ?>" >
    </div>

    <?php
    if ($has_child) :
        $selected = $selected_attributes = array(); ?>
        <input type="hidden" name="variation_id" id="variation_id" value="">
        <?php
        foreach ($attributes as $attribute => $options) :
            $esc_attribute = esc_attr(sanitize_title($attribute));
            ?>
            <div class="variations wdgk_variation wdgk-row">
                <label class="wdgk-variation-heading" for="<?php echo $esc_attribute; ?>">
                    <?php echo wc_attribute_label($attribute, $product); ?>
                    <abbr class="required" title="<?php esc_html_e('required', 'woo-donations'); ?>">*</abbr>
                </label>
                <div>
                    <?php
                    $variation_args = array(
                        'options' => $options,
                        'attribute' => $esc_attribute,
                        'product' => $product
                    );
                    wc_dropdown_variation_attribute_options(
                        $variation_args
                    ); ?>
                </div>
            </div>
            <?php 
        endforeach;
    endif; ?>

    <?php _e($note_html); ?>
    <a href="javascript:void(0)" class="button wdgk_add_donation" data-single-dp="<?php esc_attr_e($product_form == true ? 'true' : 'false') ?>" data-product-id="<?php echo esc_attr($fn_product_id); ?>" data-product-url="<?php echo esc_url($redirect_url); ?>">
        <?php esc_attr_e(wp_unslash($text),'woo-donations'); ?>
    </a>
    <input type="hidden" name="wdgk_product_id" value="" class="wdgk_product_id">
    <input type="hidden" name="wdgk_ajax_url" value="<?php echo esc_url($ajax_url) ?>" class="wdgk_ajax_url">
    <img src="<?php echo esc_url( WDGK_PLUGIN_URL . 'assets/images/ajax-loader.gif' ); ?>" alt="wdgk loader image" class="wdgk_loader wdgk_loader_img">
    <div class="wdgk_error_front"></div>
</div>
<?php if ($has_child): ?></form><?php endif; ?>