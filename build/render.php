<?php
$product_id = (isset($attributes['productId']) && !empty($attributes['productId'])) ? $attributes['productId'] : '';

$donation_form_html = "";
$additional_style = wdgk_form_internal_style();

if($additional_style != "") {
    $donation_form_html .= '<style>'.$additional_style.'</style>';
}

$donation_form_html .= do_shortcode('[wdgk_donation product_id="'.$product_id.'"]');
_e( $donation_form_html );