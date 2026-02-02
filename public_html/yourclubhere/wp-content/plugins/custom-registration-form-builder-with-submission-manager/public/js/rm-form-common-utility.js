(function ($) {
    // Enable strict mode.
    'use strict';

    // Define global RMUI object if it doesn't exist.
    if (typeof window.RMUI !== 'object') {
        window.RMUI = {};
    }

    RMUI.manageInputStates = function (selector) {
        var inputs = $(selector);
        var form = inputs.closest('form');

        if (!inputs.is('input') && (!form.hasClass('rmform-ui') || !form.hasClass('rmform-custom-form'))) {
            return;
        }

        function handleHover(element) {
            var inputElement = $(element);
            var fieldContainer = inputElement.closest('.rmform-field');
            inputElement.on('mouseover', function () {
                fieldContainer.addClass('rmform-is_hover');
            }).on('mouseout', function () {
                fieldContainer.removeClass('rmform-is_hover');
            });
        }

        function handleFocus(element) {
            var inputElement = $(element);
            var fieldContainer = inputElement.closest('.rmform-field');
            inputElement.on('focus', function () {
                fieldContainer.addClass('rmform-is_active');
            }).on('blur', function () {
                fieldContainer.removeClass('rmform-is_active');
            });
        }

        function handleFilledState(element) {
            var inputElement = $(element);
            var fieldContainer = inputElement.closest('.rmform-field');

            // Check input state on load
            if (inputElement.val().trim() !== '') {
                fieldContainer.addClass('rmform-is_filled');
            }

            // Check input state on change
            inputElement.on('input change', function () {
                if (inputElement.val().trim() !== '') {
                    fieldContainer.addClass('rmform-is_filled');
                } else {
                    fieldContainer.removeClass('rmform-is_filled');
                }
            });
        }

        function initialize() {
            inputs.each(function () {
                handleHover(this);
                handleFocus(this);
                handleFilledState(this);
            });
        }

        initialize();
        return this;
    };

})(jQuery);

jQuery(function ($) {

    jQuery(document).ready(function () {

        rm_init_total_pricing();

        // Initialize RMUI.manageInputStates on the specified elements when document is ready

        RMUI.manageInputStates('.rmform-ui input');

        //Set Dominent Color

        $(".rmformui").prepend("<a>");
        var rmFormColorRgbValue = $('.rmformui').find('a').css('color');

        /*-- Theme Color Global--*/
        var rmFormColorRgb = rmFormColorRgbValue;
        var avoid = "rgb";
        if (rmFormColorRgb) {
            var eprgbRemover = rmFormColorRgb.replace(avoid, '');
            var rmFormColor = eprgbRemover.substring(eprgbRemover.indexOf('(') + 1, eprgbRemover.indexOf(')'));
            $(':root').css('--themeColor', rmFormColor);
            
            var rmRgb = rmFormColorRgb.substr(3);
         
            rmRgb = rmRgb.split(',');
            rmRgb[0] = parseFloat((rmRgb[0].substr(1)) / 255);
            rmRgb[1] = parseFloat(rmRgb[1] / 255);
            rmRgb[2] = parseFloat((rmRgb[2].substring(0, rmRgb[2].length-1)) / 255);
            rmRgb.sort(function(a, b){return a-b});
            rmLum = Math.ceil(((rmRgb[2] + rmRgb[1]) * 100) / 2);
       
            if (rmLum > 80) {
                jQuery(":root").css("--rm-note-color", "0, 0, 0");
            } else {
                jQuery(":root").css("--rm-note-color", "255, 255, 255");
            }
        }
 

        //Multi Form

        //jQuery(".rmagic .rmrow .rminput select").parent(".rminput").addClass("rminput-note");
        //jQuery("#rmagic-progressbar li .rm-progressbar-counter").css("background-color", rmColor);
        
        
        
    
        
        // Labels Left
        
        
    
if (jQuery('.rmform-custom-form').attr('data-style') === 'label_left') {

    $('form.rmform-ui .rmform-row .rmform-row-field-wrap .rmform-col .rmform-field').each(function () {
        
       if ($(this).closest('.rm-address-field-wrap').length) {
            return;
        }
        
        if ($(this).find('.rmform-field-horizontal-row').length) {
            return;
        }
        
        var rmFormInput = $(this).find('input.rmform-control, select.rmform-control, textarea.rmform-control');
        var errorMessage = $(this).find('.rmform-error-message');
        var rmFormFieldNote = $(this).find('.rmform-note');
        var rmFormFieldHint = $(this).find('.rm-field-hint');

        if (rmFormInput.length) {
            if (errorMessage.length) {
                rmFormInput.add(errorMessage).wrapAll('<div class="rmform-control-wrap"></div>');
            } else {
                rmFormInput.wrap('<div class="rmform-control-wrap"></div>');
            }
        }

        if (rmFormFieldHint.length) {
            rmFormFieldHint.prependTo($(this).find('.rmform-control-wrap'));
        }

        if (rmFormFieldNote.length) {
            rmFormFieldNote.appendTo($(this).find('.rmform-control-wrap'));
        }
    });
}

$('form.rmform-ui').find('.rmmap_container .map').each(function() {
    var map_id = $(this).prop('id');
    var map_container = $(this);
    if(map_id){
        map_id= map_id.substr(3);
        setTimeout(function(){ if(map_container.is(':visible')){rmInitMap(map_id);}}, 1000);
    }
});

    });
    
    //RM Hover note
    
    $('.rmform-control').on({
        click: function () {
            rmHelpTextIn2(this);
        },
        focusin: function () {
            rmHelpTextIn2(this);
        },
        mouseleave: function () {
            rmHelpTextOut2(this);
        },
        focusout: function () {
            rmHelpTextOut2(this);
        }
    });

    // Hide notes on blur for all input, select, textarea elements
    $('input, select, textarea').blur(function () {
        $(this).parents('.rmform-field').find('.rmform-note').fadeOut('fast');
    });

    // Function to handle help text in
    function rmHelpTextIn2(element) {
        $(element).parents('.rmform-field').find('.rmform-note').fadeIn('fast');
    }

    // Function to handle help text out
    function rmHelpTextOut2(element) {
        $(element).parents('.rmform-field').find('.rmform-note').fadeOut('fast');
    }

});

function rm_calc_total_pricing(form_id){
    var ele_form = jQuery('form#rmform-module-'+form_id);
    var price_elems = ele_form.find('[data-rmfieldtype="price"]');
    if(price_elems.length > 0) {
        var tot_price = 0;
        price_elems.each(function(i){
            var el = jQuery(this);
            if(el.attr('disabled') == 'disabled') {
                return;
            }
            var qty = 1;
            if(el.prop("tagName") == "INPUT") {
                var el_type = el.attr('type');
                var el_name = el.attr('name');
                switch(el_type){
                    case 'text':     
                        var ele_qty = ele_form.find(':input[name="'+el_name+'_qty"]');
                         
                        if(ele_qty.length > 0) {
                            qty = ele_qty.val();
                        }
                        /* Let it fall through for price calc */
                    case 'hidden':
                        ele_price = el.data("rmfieldprice");
                        if(!ele_price)
                            ele_price = 0;
                        break;
                    case 'number':
                        ele_price = el.val();
                        if(!ele_price)
                            ele_price = 0;
                        var ele_qty = ele_form.find(':input[name="'+el_name+'_qty"]');
                         
                        if(ele_qty.length > 0) {
                            qty = ele_qty.val();
                        }
                        break;
                    case 'checkbox':
                        if(el.prop("checked")){
                        ele_val = el.val();
                        price_val = el.data("rmfieldprice");
                        ele_price = price_val[ele_val];
                        if(!ele_price)
                            ele_price = 0;
                        el_name = el_name.slice(0,-2); /* remove [] */
                        var ele_qty = ele_form.find(':input[name="'+el_name+'_qty['+ele_val+']"]');                         
                        if(ele_qty.length > 0) {
                            qty = ele_qty.val();
                        }
                        }
                        else
                            ele_price = 0;
                        break;
                    default:
                        ele_price = 0;
                        break;
                }
            } else if(el.prop("tagName") == "SELECT") {
                ele_val = el.val();
                var el_name = el.attr('name');
                if(!ele_val){
                    ele_price = 0;                      
                } else {
                    price_val = el.data("rmfieldprice");
                    ele_price = price_val[ele_val];
                    if(!ele_price)
                        ele_price = 0;  
                    
                    var ele_qty = ele_form.find(':input[name="'+el_name+'_qty"]');
                         
                    if(ele_qty.length > 0) {
                        qty = ele_qty.val();
                    }
                }
            } else {
                ele_price = 0;
            }   
            qty = parseInt(qty);
            if(isNaN(qty) || Math.sign(qty) == -1)
                qty = 1;
           tot_price += parseFloat(ele_price)*qty;
        });     
        
        /*Add cost of paid role*/
        var role_cost = 0;
        var ele_paidrole = []; //jQuery("#paid_role"+form_id.substr(4));
        if(ele_paidrole.length > 0) {
            var role_data = ele_paidrole.data("rmcustomroles");
            var user_role = ele_paidrole.data("rmdefrole");
            if(!user_role) {
                var roles_elems = ele_form.find('input[name="role_as"]');
                if(roles_elems.length > 0) {
                    user_role = jQuery('input[name="role_as"]:checked', '#'+form_id).val();
                    if(typeof user_role == 'undefined')
                        user_role = '';
                }
            }
            
            if(user_role) {
                if(typeof role_data[user_role] != 'undefined' && role_data[user_role].is_paid)
                    role_cost = parseInt(role_data[user_role].amount);
                if(isNaN(role_cost))
                    role_cost = 0;
            }
        }
        tot_price += role_cost;
        var tax_value = 0;
        if(rm_ajax.tax_enabled == 'yes' && tot_price > 0) {
           if(rm_ajax.tax_type == 'fixed') {
               tax_value = parseFloat(rm_ajax.tax_fixed);
               tot_price += tax_value;
           } else if(rm_ajax.tax_type == 'percentage') {
               tax_value = (tot_price * parseFloat(rm_ajax.tax_percentage))/100;
               tot_price += tax_value;
           }
        }
        var tot_price_ele = jQuery('form#rmform-module-'+form_id).find(".rm_total_price,.rm-total-price-widget");
        if(tot_price_ele.length > 0) {
            var price_formatting = tot_price_ele.data("rmpriceformat");
            var f_tot_price = '';
            if(price_formatting.pos == 'after')
                f_tot_price = tot_price.toFixed(2) + price_formatting.symbol;
            else
                f_tot_price = price_formatting.symbol + tot_price.toFixed(2);

            tot_price_ele.html(price_formatting.loc_total_text.replace("%s",f_tot_price));
            if(rm_ajax.tax_enabled == 'yes' && tot_price > 0) {
                if(price_formatting.pos == 'after') {
                    tot_price_ele.append('<span class="rm-total-price-taxt-info">(Included '+tax_value.toFixed(2)+price_formatting.symbol+' '+rm_ajax.tax_rename+')</span>');
                } else {
                    tot_price_ele.append('<span class="rm-total-price-taxt-info">(Included '+price_formatting.symbol+tax_value.toFixed(2)+' '+rm_ajax.tax_rename+')</span>');
                }
            }
        }
    }
}

function rm_init_total_pricing() {
    var ele_rm_forms = jQuery("form.rmform-ui");
    if(ele_rm_forms.length > 0) {
        ele_rm_forms.each(function(i) {
            var el_form = jQuery(this);
            var form_id = el_form.data('form-id');
            var price_elems = el_form.find('[data-rmfieldtype="price"]');
            if(price_elems.length > 0) {
                rm_calc_total_pricing(form_id);
                price_elems.change(function(e){       
                    rm_calc_total_pricing(form_id);
                });            
                                
                /*Get userdef price fields*/
                var ud_price_elems = price_elems.find('input[type="number"]');
                if(ud_price_elems.length > 0) {
                    ud_price_elems.keyup(function(e){       
                        rm_calc_total_pricing(form_id);
                    });
                }
                
                /*Get quantity fields*/
                var qty_elems = el_form.find('.rm_price_field_quantity');
                if(qty_elems.length > 0) {
                    qty_elems.keyup(function(e){       
                        rm_calc_total_pricing(form_id);
                    });
                    qty_elems.change(function(e){       
                        rm_calc_total_pricing(form_id);
                    });
                }
                
                /*Get role selector field if any*/
                var roles_elems = el_form.find('input[name="role_as"]');
                if(roles_elems.length > 0) {
                    roles_elems.change(function(e){       
                        rm_calc_total_pricing(form_id);
                    });
                }
            }
        });
    }    
}

/*Functions for displaying helptext tooltip*/
function rmHelpTextIn2(a) {
    var helpTextNode = jQuery(a).siblings(".rmform-note");
    var fieldWidth = jQuery(a).children().innerWidth();
    var fieldHeight = jQuery(a).parent().outerHeight();
    var topPos = fieldHeight;
    //var id = setInterval(frame, 1);
    jQuery(helpTextNode).css("width", fieldWidth + "px");
    jQuery(helpTextNode).css('top', topPos + "px");
    //jQuery(helpTextNode).css("margin-left", (fieldWidth - 100 + '%') + "px");
    //jQuery(helpTextNode).css("margin-left", 100% - fieldWidth  + "px");
    helpTextNode.fadeIn(500);
    /*function frame() {
        if (topPos === fieldHeight) {
            clearInterval(id);
        } else {
            topPos++;
            helpTextNode.css('top', topPos + "px");
            }
        }*/
    } 

function rmHelpTextOut2(a) {
    jQuery(a).siblings(".rmform-note").fadeOut('fast');
}

function rmInitGoogleApi() {
    var rm_init_map_containers = setInterval(function(){
        if (typeof rmInitMap === 'function') {
            var rm_all_maps = jQuery(".rm-map-controls-uninitialized");
            var i;
            var curr_id = '';
            if(rm_all_maps.length>0) clearInterval(rm_init_map_containers);
            for (i = 0; i < rm_all_maps.length; i++) { 
            if(jQuery(rm_all_maps[i]).is(':visible')){
            curr_id = rm_all_maps[i].getAttribute("id");
            jQuery(rm_all_maps[i]).removeClass("rm-map-controls-uninitialized");
            rmInitMap(curr_id);
            }
        }
    }
    }, 100);
}

function rm_append_field(tag, element_id) {
    jQuery('#' + element_id).append("<" + tag + " class='appendable_options'>" + jQuery('#' + element_id).children(tag + ".appendable_options").html() + "</" + tag + ">");
}

function rm_delete_appended_field(element, element_id) {
    if (jQuery(element).parents("#".element_id).children(".appendable_options").length > 1)
        jQuery(element).parent(".appendable_options").remove();
}

function rm_get_country_code_by_name(country_list,selected_country) {
    var regex = new RegExp(selected_country + "\[[A-Z{{2}}\]",'i');
    if(selected_country.toLowerCase()=='india'){
        return 'in';
    }
    else if(selected_country.toLowerCase()=='' || selected_country.toLowerCase()=='us' || selected_country.toLowerCase()=='united_states'){
        return 'us';
    }
    else if(selected_country.toLowerCase()=='canada'){
        return 'ca';
    }
   
    var country_code='';
    for(country in country_list)
    {
        var found= country.search(regex);
        if(found>=0){
            var index= country.search(/\[[A-Z]{2}\]/i);
      
            if(index>=0) 
            { 
                country_code= country.substr(index+1,2).toLowerCase();
                return country_code;
            }
        }
    }
    return country_code;
}

function scroll_down_end(element) {
    if (element.scrollTop + element.offsetHeight >= element.scrollHeight) {
        var div = jQuery(element).parent().siblings();
        jQuery(div).children().removeAttr('disabled');
    } else {
        var text_height = jQuery(element).css('font-size').replace('px', '');
        text_height = Math.ceil(parseInt(text_height));
        var field_height = Math.floor(jQuery(element).height());
        var line_per_field = Math.floor(jQuery(element).height() / text_height);
        var text = jQuery(element).val();
        var lines = text.split(/\r|\r\n|\n/);
        var count = text.length;
        
        var count = count / field_height;
        
        var count = Math.floor(count);
        
        lines = lines.length;
        count =count *line_per_field;
        if (lines > count)
            count = lines;
     
        if (count <= line_per_field) {
            count = 1;
        }
        
        if ((count * field_height) <= field_height) {
            var div = jQuery(element).parent().siblings();
            jQuery(div).children().removeAttr('disabled');
        }
    }
}

//Radio and checkbox field Field JS
document.addEventListener("DOMContentLoaded", function() {
  const rmFormFieldSelector = document.querySelectorAll('.rmform-field-vertical-row');
  
  rmFormFieldSelector.forEach(rmFormFieldSelector => {
    const rmFormFieldColCount = rmFormFieldSelector.getAttribute('data-field-col');
    rmFormFieldSelector.style.setProperty('--field-col-count', rmFormFieldColCount);
  });
  
  
    var rmDividerField = document.querySelector('.rmform-divider');
    if(rmDividerField){
         rmDividerField.parentElement.parentElement.classList.add('rmform-divider-wrap');
    }
  
});