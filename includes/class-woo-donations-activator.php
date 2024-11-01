<?php
/**
 * Fired during plugin activation
 */
class Woo_Donations_Activator {
    /**
	 * On plugin activation.
	 */
	public static function activate() {
        if (is_plugin_active('woo-donations-pro/woo-donations-pro.php')) {
            deactivate_plugins('woo-donations-pro/woo-donations-pro.php');
        }
        
        $btntext 			= "Add Donation";
        $textcolor 			= "#FFFFFF";
        $btncolor 			= "#289dcc";
        $form_title			= "Donation";
        $amount_placeholder	= "Ex.100";
        $note_placeholder	= "Note";
        $options 			= array();
        $setting 			= get_option('wdgk_donation_settings');
    
        if(isset($setting) && !empty($setting)) 	$options 			= $setting;
    
        if(!isset($setting['Text']))  			$options['Text'] 			= $btntext;
        if(!isset($setting['TextColor']))  		$options['TextColor'] 		= $textcolor;
        if(!isset($setting['Color']))  			$options['Color'] 			= $btncolor;
        if(!isset($setting['Formtitle'])) 		$options['Formtitle'] 		= $form_title;
        if(!isset($setting['AmtPlaceholder'])) 	$options['AmtPlaceholder'] 	= $amount_placeholder;
        if(!isset($setting['Noteplaceholder']))	$options['Noteplaceholder'] = $note_placeholder;

        if (!isset($setting['Product'])) {
            $id = wp_insert_post(array('post_title' => 'Donation', 'post_name' => 'donation', 'post_type' => 'product', 'post_status' => 'publish'));
            $sku = 'donation-' . $id;
            update_post_meta($id, '_sku', $sku);
            update_post_meta($id, '_tax_status', 'none');
            update_post_meta($id, '_tax_class', 'zero-rate');
            update_post_meta($id, '_visibility', 'hidden');
            update_post_meta($id, '_regular_price', 0);
            update_post_meta($id, '_price', 0);
            update_post_meta($id, '_virtual', 'yes');
            update_post_meta($id, '_sold_individually', 'yes');
            $options['Product'] = $id;
            $taxonomy = 'product_visibility';
            wp_set_object_terms($id, array( 'exclude-from-catalog', 'exclude-from-search' ), $taxonomy);

            $image_url  = plugin_dir_path( __DIR__ ) . 'assets/images/donation_thumbnail.jpg';
            $upload_dir = wp_upload_dir();

            if(file_exists($image_url)) {
                $image_data = file_get_contents($image_url);
                $filename = basename($image_url);
                if(wp_mkdir_p($upload_dir['path'])) {
                    $file = $upload_dir['path'] . '/' . $filename;
                }else{
                    $file = $upload_dir['basedir'] . '/' . $filename;
                }

                if(!empty($image_data)) {
                    file_put_contents($file, $image_data);
                }

                $wp_filetype = wp_check_filetype($filename, null );
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment( $attachment, $file, $id );
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                $res1 = wp_update_attachment_metadata( $attach_id, $attach_data );
                $res2 = set_post_thumbnail( $id, $attach_id );
            }

            update_option('wdgk_set_order_flag_status',1);
        }
        if (count($options) > 0) {
            update_option('wdgk_donation_settings', $options);
        }
	}
}