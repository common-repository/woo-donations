<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Elementor_Woo_Donations_Form extends Widget_Base {

	public function get_name() {
		return 'elementor_woo_donations_form';
	}

	public function get_title() {
		return esc_html__( 'Woo Donations', 'woo-donations' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_keywords() {
		return [ 'donation', 'woo' ];
	}

	protected function register_controls() {
		// Content Tab Start
		$this->start_controls_section(
			'section_title',
			[
				'label' => esc_html__( 'Woo Donation Button', 'woo-donations' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'product_id',
			[
				'type' => Controls_Manager::NUMBER,
				'label' => esc_html__( 'Product ID', 'woo-donations' ),
                'description' => esc_html__( 'Enter product id of which you want to show donation form.', 'woo-donations' ),
			]
		);
		$this->end_controls_section();

		// Content Tab End
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

        $product_id = (!empty($settings['product_id'])) ? $settings['product_id'] : '';
        
        $donation_button_html = "";
        $donation_button_html .= do_shortcode('[wdgk_donation product_id="'.$product_id.'"]');
        _e( $donation_button_html );
	}
}

Plugin::instance()->widgets_manager->register( new Elementor_Woo_Donations_Form() );