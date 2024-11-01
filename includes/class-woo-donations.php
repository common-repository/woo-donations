<?php
/**
 * The file that defines the core plugin class
 */
class Woo_Donations {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and the public-facing side of the site.
	 */
	public function __construct() {
		if ( defined( 'WDGK_BUILD' ) ) {
			$this->version = WDGK_BUILD;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woo-donations';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks with WordPress.
	 */
	private function load_dependencies() {
        /**
         * The class responsible to define general plugin functions 
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woo-donations-functions.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woo-donations-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woo-donations-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woo-donations-public.php';

		$this->loader = new Woo_Donations_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woo_Donations_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu_page' );

        $this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'wdgk_woo_admin_order_items_column' );
        $this->loader->add_filter( 'woocommerce_shop_order_list_table_columns', $plugin_admin, 'wdgk_woo_admin_order_items_column' );

        $this->loader->add_action( 'woocommerce_shop_order_list_table_custom_column', $plugin_admin, 'wdgk_shop_order_custom_column', 10, 2 );
        $this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'wdgk_order_items_column_cnt' );
        
        $this->loader->add_action( 'wp_ajax_wdgk_product_select_ajax', $plugin_admin, 'wdgk_product_select_ajax_callback' );
        $this->loader->add_action( 'wp_ajax_nopriv_wdgk_product_select_ajax', $plugin_admin, 'wdgk_product_select_ajax_callback' );
        
        $this->loader->add_action( 'enqueue_block_editor_assets', $plugin_admin, 'wdgk_block_editor_script' );

		/* Edit product meta fields */
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'wdgk_product_data_tabs', 10, 3 );
		$this->loader->add_action( 'woocommerce_product_data_panels', $plugin_admin, 'wdgk_product_data_panel' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'wdgk_process_product_meta' );
		$this->loader->add_filter( 'product_type_options', $plugin_admin, 'wdgk_add_product_type_option', 11, 1 );
		$this->loader->add_action( 'save_post_product', $plugin_admin, 'wdgk_save_post_product', 50, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin.
	 */
	private function define_public_hooks() {

		$plugin_public = new Woo_Donations_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        $product = $cart = $checkout = "";
        $options = wdgk_get_wc_donation_setting();
        if (isset($options['Product']))     $product = $options['Product'];
        if (isset($options['Cart']))        $cart = $options['Cart'];
        if (isset($options['Checkout']))    $checkout = $options['Checkout'];

        if (!empty($product) && $cart == 'on') {
            $this->loader->add_action( 'woocommerce_proceed_to_checkout', $plugin_public, 'cart_page_donation_form' );
        }
        
        if (!empty($product) && $checkout == 'on') {
            $this->loader->add_action( 'woocommerce_before_checkout_form', $plugin_public, 'checkout_page_donation_form' );
        }
        
        $this->loader->add_shortcode( 'wdgk_donation', $plugin_public, 'wdgk_donation_form_shortcode_html' );

        $this->loader->add_action( 'wp_head', $plugin_public, 'wdgk_set_button_text_color' );
        $this->loader->add_filter( 'woocommerce_add_cart_item_data', $plugin_public, 'wdgk_add_cart_item_data', 10, 3 );
        $this->loader->add_filter( 'woocommerce_before_calculate_totals', $plugin_public, 'wdgk_before_calculate_totals', 1000, 1 );

        $this->loader->add_filter( 'woocommerce_cart_item_price', $plugin_public, 'wdgk_filter_cart_item_price', 10, 3 );
        $this->loader->add_filter( 'woocommerce_cart_item_subtotal', $plugin_public, 'wdgk_show_product_discount_order_summary', 10, 3 );

        $this->loader->add_action( 'wp_ajax_wdgk_donation_form', $plugin_public, 'wdgk_donation_ajax_callback' );
        $this->loader->add_action( 'wp_ajax_nopriv_wdgk_donation_form', $plugin_public, 'wdgk_donation_ajax_callback' );

        $this->loader->add_filter( 'woocommerce_get_item_data', $plugin_public, 'wdgk_plugin_republic_get_item_data', 10, 2 );
        $this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $plugin_public, 'wdgk_plugin_republic_checkout_create_order_line_item', 10, 4 );
        $this->loader->add_filter( 'woocommerce_order_item_name', $plugin_public, 'wdgk_plugin_republic_order_item_name', 10, 2 );

        $this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'wdgk_thankyou_change_order_status' );
		$this->loader->add_filter( 'woocommerce_locate_template', $plugin_public, 'wdgk_intercept_wc_template', 20, 3);
        
        $this->loader->add_filter( 'woocommerce_cart_item_quantity', $plugin_public, 'wdgk_cart_item_quantity', 10, 3 );

        $this->loader->add_filter( 'woocommerce_loop_add_to_cart_link', $plugin_public, 'wdgk_loop_add_to_cart_link', 10, 3 );
        $this->loader->add_filter( 'woocommerce_product_add_to_cart_text', $plugin_public, 'product_add_to_cart_text', 10, 2 );
		
        $this->loader->add_filter( 'woocommerce_is_sold_individually', $plugin_public, 'wdgk_product_sold_individually', 20, 2 );
		
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}