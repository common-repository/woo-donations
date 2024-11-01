<?php
/**
 * Template for the "Donation Form" tab for editing products
 */
if (!defined('ABSPATH')) exit;

global $post;
?>
<div id="wdgk_donation_form_data" class="panel woocommerce_options_panel hidden">
    <div class="options_group">
        <p class="wdgk_shortcode form-field">
            <label for="wdgk_shortcode"><?php esc_html_e('Shortcode', 'woo-donations'); ?></label>
            <span class="wrap">
				<input type="text" id="wdgk_shortcode" readonly="readonly" onclick="this.select()" value="[wdgk_donation product_id=&quot;<?php esc_attr_e($post->ID); ?>&quot;]">
				<?php
                echo wc_help_tip(__('Add this shortcode where you want to display the donation form.', 'woo-donations')); ?>
			</span>
        </p>
        <?php

        // Checkbox field 1
        woocommerce_wp_checkbox( array(
            'id'            => 'wdgk_add_note',
            'value'         => get_post_meta($post->ID, 'wdgk-settings[wdgk_add_note]', true),
            'wrapper_class' => '',
            'label'         => __( 'Add Note', 'woo-donations' ),
            'cbvalue'       => esc_attr('on'),
            'description'   => __( 'Enable to display donation note on form', 'woo-donations' )
        ) );
        ?>
    </div>

    <div class="options_group">
        <?php
        woocommerce_wp_text_input(
            array(
                'id' => "wdgk_btntext",
                'name' => "wdgk_btntext",
                'value' => get_post_meta($post->ID, 'wdgk-settings[wdgk_btntext]', true),
                'label' => __('Button Text', 'woo-donations'),
                'wrapper_class' => 'form-field',
                'placeholder'   => __( 'Eg: Add Donation', 'woo-donations' ),
                'desc_tip' => true,
                'description' => __('Add Donation button text. Default: Add Donation','woo-donations')
            )
        );

        woocommerce_wp_text_input(
            array(
                'id' => "wdgk_title",
                'name' => "wdgk_title",
                'value' => get_post_meta($post->ID, 'wdgk-settings[wdgk_title]', true),
                'label' => __('Donation Form Title', 'woo-donations'),
                'wrapper_class' => 'form-field',
                'placeholder'   => __( 'Eg: Donation', 'woo-donations' ),
                'desc_tip' => true,
                'description' => __('Add Donation form title.','woo-donations')
            )
        );

        woocommerce_wp_text_input(
            array(
                'id' => "wdgk_amt_place",
                'name' => "wdgk_amt_place",
                'value' => get_post_meta($post->ID, 'wdgk-settings[wdgk_amt_place]', true),
                'label' => __('Amount Placeholder Text', 'woo-donations'),
                'wrapper_class' => 'form-field',
                'placeholder'   => __( 'Eg: Ex. 100', 'woo-donations' ),
                'desc_tip' => true,
                'description' => __('Add Donation amount placeholder text.','woo-donations')
            )
        );
        
        woocommerce_wp_textarea_input( array(
            'id'          => 'wdgk_note_place',
            'value' => get_post_meta($post->ID, 'wdgk-settings[wdgk_note_place]', true),
            'label'       => __( 'Note Placeholder Text', 'woo-donations' ),
            'placeholder'   => __( 'Eg: Note', 'woo-donations' ),
            'desc_tip'    => true,
            'description' => __( 'Add Donation note placeholder text.', 'woo-donations' ),
        ));
        ?>
    </div>

    <div class="options_group">
        <p class="wdgk-ask-review">
            <a href="https://wordpress.org/support/plugin/woo-donations/reviews/" target="_blank"><?php esc_html_e('If you like Woo Donations and want to support the further growth and development of the plugin, please consider a 5-star rating on wordpress.org.','woo-donations'); ?></a>
        </p>
    </div>
    <script>
        (function ($) {
            $(window).bind("load", function () {
                wdgk_show_hide_donable_panel();

                if ($('#_regular_price').val() == '') {
                    $('#_regular_price').val(0);
                }
            });

            $(window).on("load", function() {
                $("#product-type").trigger("change");
            });

            $('input#_donatable').on('change', function () {
                wdgk_show_hide_donable_panel();
            });

            $('#product-type').change(function(){
                const selected_type = $(this).val();
                if(selected_type == 'simple' || selected_type == 'variable') {
                    setTimeout(() => {
                        wdgk_show_hide_donable_panel();
                    }, 200);
                }
            });

            function wdgk_show_hide_donable_panel() {
                const is_donable = $('input#_donatable:checked').length;
                if (is_donable) {
                    $('.show_if_donatable').show();
                } else {
                    $('.show_if_donatable').hide();
                }
            }
        })(jQuery);
    </script>
</div>