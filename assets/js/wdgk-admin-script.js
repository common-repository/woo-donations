
jQuery(document).ready(function ($) {
    /**
     * Search product for donation JS
     */
    jQuery('.wdgk_select_product').select2({
        ajax: {
            type: 'POST',
            url: wdgkObj.ajaxurl,
            dataType: 'json',
            data: (params) => {
                return {
                    'search': params.term,
                    'action': 'wdgk_product_select_ajax',
                }
            },
            processResults: (data, params) => {
                const results = data.map(item => {
                    return {
                        id: item.id,
                        text: item.title,
                    };
                });
                return {
                    results: results,
                }
            },
        },
        minimumInputLength: 3
    });

    jQuery(".wdgk_shortcode_copy").click(function (event) {
        event.preventDefault();
        var text = jQuery(this).text();
        wdgkCopyToClipboard(text, true, "Copied");
    });

    /** Disable checkbox for WPML translated products */
    if(jQuery(".wdgk_disable").length > 0) {
        jQuery(".wdgk_disable").find("#_donatable").attr("disabled", true);
    }

});

function wdgkCopyToClipboard(value, showNotification, notificationText) {
    var $temp = jQuery("<input>");
    jQuery("body").append($temp);
    $temp.val(value).select();
    document.execCommand("copy");
    $temp.remove();

    if (typeof showNotification === 'undefined') {
        showNotification = true;
    }
    if (typeof notificationText === 'undefined') {
        notificationText = "Copied to clipboard";
    }


    if (showNotification) {

        jQuery(".wdgk_shortcode_copy").attr('data-balloon', notificationText);

        setTimeout(function () {
            jQuery(".wdgk_shortcode_copy").attr('data-balloon', 'Click to copy');
        }, 1000);
    }
}