var decimal_sep, thousand_sep;
decimal_sep = wdgk_obj.options.decimal_sep;        // woo commerce decimal separator
thousand_sep = wdgk_obj.options.thousand_sep;      // woo commerce thousand separator

jQuery(document).ready(function ($) {
    /** Not allow alphabats on keypress */
    jQuery('.wdgk_donation').on('keydown', function (e) {
        if (e.which == 13) {
            jQuery(this).closest('.wdgk_donation_content').find(".wdgk_add_donation").trigger("click");
        }

        // No letters
        if (e.which >= 65 && e.which <= 90) {
            e.preventDefault();
            return false;
        }
    });

    /** Prevent redirection on donation form for variable product */
    jQuery('body').on("keypress", '.wdgk_donation_content input[name="donation-price"]', function (e) {
        if (e.which === 13) { // 13 is the Enter key
            jQuery('.wdgk_add_donation').click();
            return false;
        }
    });
    jQuery('#wdgk_variation_submit').on('submit', function(e){
        // validation code here
        e.preventDefault();
    });

    jQuery('body').on("click", ".wdgk_add_donation", function () {
        var note = "";
        var price = "";
        var variation_id = "";
        var variation_obj = {};
        var decimal_price = "";

        var variation_id = jQuery(this).closest('.wdgk_donation_content').find('#variation_id').val();
        
        var price_elem = jQuery(this).closest('.wdgk_donation_content').find("input[name='donation-price']");
        if (price_elem) {
            updated_price = wdgk_updatedInputprice(price_elem.val());
            price_elem.val(updated_price);
            price = updated_price;
            var replace_decimal_regex = new RegExp('\\' + decimal_sep, "g");
            decimal_price = price.replace(replace_decimal_regex, '.');      // replace decimal sparator to dot
        }

        if (jQuery(this).closest('.wdgk_donation_content').find('.donation_note').val()) {
            var note = jQuery(this).closest('.wdgk_donation_content').find('.donation_note').val();
            var lines = [];
            $.each(note.split(/\n/), function (i, line) {
                if (line) {
                    lines.push(jQuery.trim(line));
                } else {
                    lines.push("");
                }
            });
            var note_text = JSON.stringify(lines);
        }
        var ajaxurl = jQuery('.wdgk_ajax_url').val();
        var product_id = jQuery(this).attr('data-product-id');
        var redirect_url = jQuery(this).attr('data-product-url');
        var single_dp_id = jQuery(this).attr('data-single-dp');


        if (decimal_price == "") {
            jQuery(this).closest('.wdgk_donation_content').find(".wdgk_error_front").text("Please enter a value!!");
            return false;
        } else {
            var pattern = new RegExp(/^[0-9.*]/);
            if (!pattern.test(decimal_price) || decimal_price < 0.01) {
                jQuery(this).closest('.wdgk_donation_content').find(".wdgk_error_front").text("Please enter valid value!!");
                return false;
            }
        }

        // update function for allow comma in donation price
        if (wdgk_isNumber(decimal_price)) {
            jQuery(this).closest('.wdgk_donation_content').find(".wdgk_error_front").text("Please enter numeric value!!");
            return false;
        }
        if(variation_id != undefined && variation_id == '') {
            jQuery(this).closest('.wdgk_donation_content').find(".wdgk_error_front").text("Please fill required fields!!");
            return false;
        }else{
            jQuery(this).closest('.wdgk_donation_content').find(".wdgk_error_front").text("");
        }

        jQuery(this).closest('.wdgk_donation_content').find('.wdgk_loader').removeClass("wdgk_loader_img");

        // set new cookie for display price with comma
        if(single_dp_id == 'true') {
            if(variation_id != undefined) {
                product_id = variation_id;
                jQuery('.wdgk_variation.wdgk-row').each(function() {
                    var attr_name = jQuery(this).find("select").attr("data-attribute_name");
                    var attr_value = jQuery(this).find("select").find(":selected").val();
                    variation_obj[attr_name] = attr_value;
                });
                setCookie('wdgk_variation_product:'+variation_id, JSON.stringify(variation_obj), 1);
            }
            setCookie('wdgk_product_display_price:'+product_id, price, 1);
            setCookie('wdgk_product_price:'+product_id, decimal_price, 1);
            setCookie('wdgk_donation_note:'+product_id, note_text, 1);            
        }else{
            setCookie('wdgk_product_display_price', price, 1);
            setCookie('wdgk_product_price', decimal_price, 1);
            setCookie('wdgk_donation_note', note_text, 1);
        }

        var $data = {
            action: 'wdgk_donation_form',
            product_id: product_id,
            price: decimal_price,
            note: note,
            redirect_url: redirect_url
        }
        if(variation_id != undefined) { 
            $data.product_type = 'variation';
            $data.variation_id = variation_id;
        }

        jQuery.ajax({
            url: ajaxurl,
            data: $data,
            type: 'POST',
            success: function (data) {
                var redirect = jQuery.parseJSON(data);
                if (redirect.error == "true") {
                    jQuery(this).closest('.wdgk_donation_content').find(".wdgk_error_front").text("Please enter valid value!!");
                    jQuery(this).closest('.wdgk_donation_content').find('.wdgk_loader').addClass("wdgk_loader_img");
                    return false;
                } else {
                    document.location.href = redirect.url;
                }
            }
        });
    });

});

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000);
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(";");
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == " ") {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}
// 
function wdgk_isNumber(price) {
    var regex = /^[0-9.,\b]+$/;
    if (!regex.test(price)) return false;
}

/* Returns filtered product price */
function wdgk_updatedInputprice(input_val) {
    let allow_number = new RegExp('[^0-9\\' + decimal_sep + ']', 'g');  // allow only number and decimal                "/[^\d.]/g"
    let remove_ml_sep = new RegExp('\\' + decimal_sep + '+$');          // remove multiple decimal from last of string     "/\.+$/"
    let remove_from_start_end = new RegExp('^\\' + decimal_sep + '+|\\' + decimal_sep + '+$', 'g');  // remove decimal from start and end of string  "/^,+|,+$/g"
    var updated_price = input_val.replace(allow_number, "").replace(remove_ml_sep, "").replace(remove_from_start_end, "");

    if (updated_price != '' && updated_price.includes(decimal_sep)) {
        let new_val = updated_price.split(decimal_sep);
        updated_price = new_val.shift() + decimal_sep + new_val.join('');
    }
    return updated_price;
}
