<?php
add_action('elementor/widgets/register','wdgk_widget_registered');

function wdgk_widget_registered() {
	if( !class_exists('Elementor\Widget_Base') ){
		return;
	}

	$elementor_widgets = array(
		'woo-donations-form'
	);

	$elementor_widgets = apply_filters('wdgk_elementor_widget',$elementor_widgets);

	if ( is_array($elementor_widgets) && !empty($elementor_widgets) ) {
		foreach ( $elementor_widgets as $widget ){
			$widget_file = 'plugins/elementor/widget/'.$widget.'.php';
			$template_file = locate_template($widget_file);
			if ( !$template_file || !is_readable( $template_file ) ) {
				$template_file = __DIR__ . '/widgets/'.$widget.'.php';
			}
			if ( $template_file && is_readable( $template_file ) ) {
				include_once $template_file;
			}
		}
	}
}


add_action('elementor/editor/after_enqueue_styles','wdgk_editor_style');

function wdgk_editor_style() {
	$additional_style = wdgk_form_internal_style();
    if(isset($additional_style) && !empty($additional_style)) { ?>
        <style>
            <?php _e($additional_style); ?>
        </style>
    <?php
	}
}