<?php

/**
 * The public-facing functionality of the plugin.
 */
class Woo_Donations_Public {
    /**
     * The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name . '-front-style', plugin_dir_url(__DIR__) . 'assets/css/wdgk-front-style.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script($this->plugin_name . '-front-script', plugin_dir_url(__DIR__) . 'assets/js/wdgk-front-script.js', array('jquery'), $this->version, array('in_footer' => true));

        $decimal_separator  = wc_get_price_decimal_separator();
        $thousand_separator = wc_get_price_thousand_separator();
        $wdgk_options = ["decimal_sep" => $decimal_separator, "thousand_sep" => $thousand_separator];
        wp_localize_script($this->plugin_name . '-front-script', 'wdgk_obj', array('ajaxurl' => admin_url('admin-ajax.php'), 'options' => $wdgk_options));
    }

    /** Cart page html */
    public function cart_page_donation_form() {
        global $woocommerce;
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
        $this->wdgk_donation_form_front_html($cart_url);
    }

    /** Checkout page html */
    public function checkout_page_donation_form($checkout) {
        global $woocommerce;
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : $woocommerce->cart->get_checkout_url();
        $this->wdgk_donation_form_front_html($checkout_url);
    }

    public function wdgk_donation_form_front_html($redurl) {
        global $woocommerce;
        $product = $text = $note = $note_html = $donation_price = $donation_note = "";
        $form_title            = "Donation";
        $amount_placeholder    = "Ex.100";
        $note_placeholder    = "Note";

        $options = wdgk_get_wc_donation_setting();

        if (isset($options['Product'])) {
            $product = $options['Product'];
        }

        $post_status = get_post_status($product);
        if ($post_status != 'publish') {
            echo '<div class="wdgk_error_front">Donation product not found!</div>';
            return;
        }

        if (wc()->cart) {
            $cart_count = is_object($woocommerce->cart) ? $woocommerce->cart->get_cart_contents_count() : '';
            if ($cart_count != 0) {
                $cartitems = $woocommerce->cart->get_cart();
                if (!empty($cartitems) && isset($cartitems)) {
                    foreach ($cartitems as $item => $values) {
                        $product_id =  $values['product_id'];
                        $donation_price = (isset($values['donation_price'])) ? $values['donation_price'] : '';
                        if ($product_id == $product) {
                            $donation_price = isset($_COOKIE['wdgk_product_display_price']) ? $_COOKIE['wdgk_product_display_price'] : $donation_price;
                            if (isset($values['donation_note'])) $donation_note = str_replace("<br />", "\n", $values['donation_note']);
                        }
                    }
                }
            }
        }

        if (isset($options['Text'])) {
            $text = $options['Text'];
        }
        if (isset($options['Note'])) {
            $note = $options['Note'];
        }
        if (isset($options['Formtitle'])) {
            $form_title = $options['Formtitle'];
        }
        if (isset($options['AmtPlaceholder'])) {
            $amount_placeholder = $options['AmtPlaceholder'];
        }
        if (isset($options['Noteplaceholder'])) {
            $note_placeholder = $options['Noteplaceholder'];
        }
        if (!empty($product) && $note == 'on') {
            $note_html = '<textarea id="w3mission" rows="3" cols="20" placeholder="' . esc_attr(wp_unslash($note_placeholder), 'woo-donations') . '" name="donation_note" class="donation_note">' . wp_unslash($donation_note) . '</textarea>';
        }

        if (!empty($product)) {
            $product_id = $product;
            $product = wc_get_product($product);

            $has_child = is_a($product, 'WC_Product_Variable') && $product->has_child();

            $ajax_url        = admin_url('admin-ajax.php');
            $current_cur     = get_woocommerce_currency();
            $cur_syambols     = get_woocommerce_currency_symbols();

            if (!empty($donation_price)) {
                $decimal_separator = wc_get_price_decimal_separator();
                $thousand_separator = wc_get_price_thousand_separator();
                $price_decimals = wc_get_price_decimals();
                if (!is_numeric($donation_price)) {
                    $donation_price = str_replace($decimal_separator, '.', $donation_price);
                }
                $donation_price = number_format($donation_price, $price_decimals, $decimal_separator, $thousand_separator);
            }

            if ($has_child) {
                require 'templates/class-wdgk-form.php';
            } else {
                _e(
                    '<div class="wdgk_donation_content">
                        <h3>' . esc_attr(wp_unslash($form_title)) . '</h3>
                        <div class="wdgk_display_option">
                            <span>' . esc_attr($cur_syambols[$current_cur]) . '</span>
                            <input type="text" name="donation-price" class="wdgk_donation" 
                                   placeholder="' . esc_attr(wp_unslash($amount_placeholder)) . '" 
                                   value="' . esc_attr($donation_price) . '">
                        </div>
                        ' . $note_html . '
                        <a href="javascript:void(0)" class="button wdgk_add_donation" 
                           data-product-id="' . esc_attr($product_id) . '"
                           data-product-url="' . esc_url($redurl) . '">
                            ' . esc_attr(wp_unslash($text)) . '
                        </a>
                        <input type="hidden" name="wdgk_product_id" value="" class="wdgk_product_id">
                        <input type="hidden" name="wdgk_ajax_url" value="' . esc_url($ajax_url) . '" class="wdgk_ajax_url">
                        <img src="' . WDGK_PLUGIN_URL . 'assets/images/ajax-loader.gif" class="wdgk_loader wdgk_loader_img">
                        <div class="wdgk_error_front"></div>
                    </div>'
                );
            }
        }
    }

    /**
     * Return html of Donation Form
     */
    public function wdgk_donation_form_shortcode_html($atts = array()) {
        return Woo_Donations_Public::wdgk_donation_form($atts);
    }

    /**
     * Return html of Donation Form
     */
    public static function wdgk_donation_form(array $value) {
        ob_start();

        require 'templates/class-wdgk-form.php';

        $r = ob_get_contents();
        ob_end_clean();

        return $r;
    }

    /**
     * Set button text color
     */
    public function wdgk_set_button_text_color() {
        $additional_style = wdgk_form_internal_style();
        if (isset($additional_style) && !empty($additional_style)) { ?>
            <style>
                <?php _e($additional_style); ?>
            </style>
<?php
        }
    }

    /**
     * Add to cart product hook
     */
    public function wdgk_add_cart_item_data($cart_item_data, $product_id, $variation_id) {

        $donatable_prods = array();
        foreach ($_COOKIE as $cookie_key => $cookie_value) {
            if (strpos($cookie_key, 'wdgk_product_price:') === 0 || strpos($cookie_key, 'wdgk_donation_note:') === 0) {
                $donatable_prods[$cookie_key] = $cookie_value;
            }
        }

        if (!empty($donatable_prods)) {

            $product = wc_get_product($product_id);
            if (isset($variation_id) && !empty($variation_id)) {
                $id = wp_get_post_parent_id($variation_id);
            } else {
                $id = $product_id;
            }
            $is_donatable = wdgk_is_donatable($id);

            if ($is_donatable) {
                // Check cookies to update donation price on cart
                if (isset($variation_id) && !empty($variation_id)) {
                    $save_id = $variation_id;
                    $cookie_name = sprintf('wdgk_variation_product:%s', $variation_id);
                    $cookie_value = isset($_COOKIE[$cookie_name]) ? $variation_id : '';

                    $new_cookie_name = sprintf('wdgk_variation_product:%s', $product_id);
                    setcookie($new_cookie_name, $cookie_value,  time() + 86400, "/"); // 1 day
                } else {
                    $save_id = $product_id;
                }
                $save_id = (isset($variation_id) && !empty($variation_id)) ? $variation_id : $product_id;

                $product_price_key = sprintf('wdgk_product_price:%s', $save_id);
                $product_note_key  = sprintf('wdgk_donation_note:%s', $save_id);

                if (array_key_exists($product_price_key, $donatable_prods)) {
                    $donation_note = json_decode(stripslashes($donatable_prods[$product_note_key]));

                    $cart_item_data['donation_price'] = $donatable_prods[$product_price_key];
                    if (isset($donation_note) && !empty($donation_note))        $cart_item_data['donation_note'] = implode("<br />", $donation_note);
                }
            }
        }

        if (!isset($cart_item_data['donation_price'])) {
            $pid = "";

            $options = wdgk_get_wc_donation_setting();
            if (isset($options['Product'])) {
                $pid = $options['Product'];
            }

            if (isset($_COOKIE['wdgk_product_price'])) {
                if ($product_id == $pid) {
                    $donation_note = json_decode(stripslashes($_COOKIE['wdgk_donation_note']));
                    $cart_item_data['donation_price'] = $_COOKIE['wdgk_product_price'];
                    if (isset($donation_note) && !empty($donation_note))        $cart_item_data['donation_note'] = implode("<br />", $donation_note);
                }
            }
        }

        return $cart_item_data;
    }

    /**
     * Set donation price in total
     */
    public function wdgk_before_calculate_totals($cart_obj) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        // Iterate through each cart item
        foreach ($cart_obj->get_cart() as $key => $value) {

            if (isset($value['donation_price'])) {
                $price = $value['donation_price'];
                $value['data']->set_price(($price));
            }
        }
    }

    /**
     * Filter cart item price
     */
    public function wdgk_filter_cart_item_price($price_html, $cart_item, $cart_item_key) {
        if (isset($cart_item['donation_price'])) {
            return wc_price($cart_item['donation_price']);
        }
        return $price_html;
    }

    /**
     * Set donation price in subtotal
     */
    public function wdgk_show_product_discount_order_summary($total, $cart_item, $cart_item_key) {
        //Get product object
        if (isset($cart_item['donation_price'])) {
            $total = wc_price($cart_item['donation_price']  * $cart_item['quantity']);
        }
        // Return the html
        return $total;
    }

    /** Ajax response on Add donation click  */
    public function wdgk_donation_ajax_callback() {
        $product_id   = sanitize_text_field($_POST['product_id']);
        $price        = sanitize_text_field($_POST['price']);
        $redirect_url = sanitize_text_field($_POST['redirect_url']);
        $variation_id = (isset($_POST['variation_id']) && !empty($_POST['variation_id'])) ? sanitize_text_field($_POST['variation_id']) : '';

        $quantity = 1;
        wdgk_add_donation_product_to_cart($product_id, $quantity, $variation_id);

        $product = wc_get_product($product_id);

        $response = array();
        $response['url'] = $redirect_url;
        $response = json_encode($response);
        _e($response);
        wp_die();
    }

    /**
     * Filter cart time data
     */
    public function wdgk_plugin_republic_get_item_data($item_data, $cart_item_data) {
        if (isset($cart_item_data['donation_price']) && isset($cart_item_data['donation_note']) && !empty($cart_item_data['donation_note'])) {
            $item_data[] = array(
                'key' => __('Description', 'woo-donations'),
                'value' => wp_unslash($cart_item_data['donation_note'])
            );
        }
        return $item_data;
    }

    /**
     * Filter checkout order item
     */
    public function wdgk_plugin_republic_checkout_create_order_line_item($item, $cart_item_key, $values, $order) {
        if (isset($values['donation_note'])) {
            $item->add_meta_data(
                __('Description', 'woo-donations'),
                wp_unslash($values['donation_note']),
                true
            );
        }
    }

    /**
     * Filter cart item name
     */
    public function wdgk_plugin_republic_order_item_name($product_name, $item) {
        if (isset($item['donation_note']) && isset($item['donation_price'])) {

            $product_name .= sprintf(
                '<ul><li>%s: %s</li></ul>',
                __('Description', 'woo-donations'),
                wp_unslash($item['donation_note'])
            );
        }
        return $product_name;
    }

    /**
     * After woocommerce order success
     */
    public function wdgk_thankyou_change_order_status($order_id) {
        $donation_product   = "";
        $order_donation_items = [];
        $options = wdgk_get_wc_donation_setting();
        $order              =   wc_get_order($order_id);
        $items              =   $order->get_items();

        foreach ($items as $item) {
            $item_id = $item['product_id'];

            $is_donatable = wdgk_is_donatable($item_id);
            if ($is_donatable) {
                $donation_product = $item_id;
            } else {
                if (isset($options['Product'])) {
                    $donation_product   = $options['Product'];
                }
            }

            if ($donation_product == $item_id) {
                $order_donation_items[] = $item_id;
            }
        }

        if (!empty($order_donation_items)) {
            $donation_item_str = implode(",", $order_donation_items);
            $order->update_meta_data('wdgk_donation_order_flag', $donation_item_str);   // set donation order flag 
            $order->save();
        }
    }

    /**
     * Woocommerce template override
     */
    public function wdgk_intercept_wc_template($template, $template_name, $template_path) {
        //Return if the template has been overwritten in yourtheme/woocommerce/XXX
        if ($template[strlen($template) - strlen($template_name) - 2] === 'e') {
            return $template;
        }

        $product_id = '';
        $options = wdgk_get_wc_donation_setting();
        if (isset($options['Product']))      $product_id = $options['Product'];

        $path = plugin_dir_path(dirname(__FILE__)) . 'includes/wc-templates/';

        if (is_product()) {
            global $product;
            $product = wc_get_product(get_the_ID());
            if ($product->is_type('simple') || $product->is_type('variable')) {
                switch ($template_name) {
                    case 'loop/no-products-found.php':
                        $template = $path . $template_name;
                        break;

                    case 'loop/price.php':
                        if (!is_null($product) && (wdgk_is_donatable($product->get_id()) || $product_id == $product->get_id())) {
                            $template = $path . $template_name;
                        }
                        break;

                    case 'single-product/price.php':
                    case 'single-product/add-to-cart/variation-add-to-cart-button.php':
                        if (wdgk_is_donatable(get_queried_object_id()) || $product_id == get_queried_object_id()) {
                            $template = $path . $template_name;
                        }
                        break;

                    case 'single-product/add-to-cart/simple.php':
                    case 'single-product/add-to-cart/variable.php':
                        if (wdgk_is_donatable(get_queried_object_id()) || $product_id == get_queried_object_id()) {
                            $template = $path . 'single-product/add-to-cart/product.php';
                        }
                        break;

                    default:
                        break;
                }
            }
        }


        return apply_filters('wdgk_locate_template', $template, $template_name, $template_path);
    }

    /**
     * Hide donation product quantity
     */
    public function wdgk_cart_item_quantity($product_quantity, $cart_item_key, $cart_item) {
        $donation_product = "";
        $product_id = $cart_item['product_id'];
        $options = wdgk_get_wc_donation_setting();

        $is_donatable = wdgk_is_donatable($product_id);
        if ($is_donatable) {
            return '';
        } else {
            if (isset($options['Product'])) {
                $donation_product   = $options['Product'];
                if ($donation_product == $product_id) {
                    return '';
                }
            }
        }
        return $product_quantity;
    }


    /**
     * Change "Add to Cart" Button Text
     */
    public function product_add_to_cart_text($text, $product): string {
        if (wdgk_is_donatable($product->get_id())) {
            return __('Learn more', 'woo-donations');
        }
        return $text;
    }

    /**
     * Change "Add to Cart" link
     */
    public function wdgk_loop_add_to_cart_link($html, $product, $args): string {
        if (wdgk_is_donatable($product->get_id())) {
            return sprintf(
                '<a href="%s" class="button" %s>%s</a>',
                esc_url($product->get_permalink()),
                isset($args['attributes']) ? wc_implode_html_attributes($args['attributes']) : '',
                esc_html($product->add_to_cart_text())
            );
        }
        return $html;
    }

    /**
     * Disable quantity field for donation products
     */
    public function wdgk_product_sold_individually($return, $product) {
        $productId = "";
        $options = wdgk_get_wc_donation_setting();
        if (isset($options['Product'])) {
            $productId = $options['Product'];
        }
        if (wdgk_is_donatable($product->get_id()) || $productId == $product->get_id()) {
            $return = true;
        }
        return $return;
    }
}
