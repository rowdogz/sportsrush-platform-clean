jQuery(document).ready(function() {
    jQuery('.datepicker').datepicker({
        dateFormat: jQuery('.datepicker').data('dateformat'),
        changeMonth: true,
        changeYear: true,
        yearRange: '1900:+50',
        onSelect: function (date, datepicker) { 
            if(date != "") {
                jQuery(this).parent().removeClass('rmform-has-error');
                jQuery(this).siblings('span.rmform-error-message').text('');
                jQuery(this).attr("aria-invalid","false");
            }
        }
    });

    jQuery('.bdaydatepicker').each(
        function() {
            jQuery(this).datepicker({
                dateFormat: jQuery(this).data('dateformat'),
                changeMonth: true,
                changeYear: true,
                yearRange: '1900:+50',
                minDate: jQuery(this).attr('required_min_range'),
                maxDate: jQuery(this).attr('required_max_range'),
                onSelect: function (date, datepicker) { 
                if(date != "") {
                    jQuery(this).parent().removeClass('rmform-has-error');
                    jQuery(this).siblings('span.rmform-error-message').text('');
                    jQuery(this).attr("aria-invalid","false");
                }
            }
        });
    });
});

var rmReCaptchas = [];

function update_state_dropdown($field_id) {
    const options = {
        US: [
            { value: '', text: '--Select State--' },
            { value: 'AL', text: 'Alabama' },
            { value: 'AK', text: 'Alaska' },
            { value: 'AZ', text: 'Arizona' },
            { value: 'AR', text: 'Arkansas' },
            { value: 'CA', text: 'California' },
            { value: 'CO', text: 'Colorado' },
            { value: 'CT', text: 'Connecticut' },
            { value: 'DE', text: 'Delaware' },
            { value: 'DC', text: 'District of Columbia' },
            { value: 'FL', text: 'Florida' },
            { value: 'GA', text: 'Georgia' },
            { value: 'HI', text: 'Hawaii' },
            { value: 'ID', text: 'Idaho' },
            { value: 'IL', text: 'Illinois' },
            { value: 'IN', text: 'Indiana' },
            { value: 'IA', text: 'Iowa' },
            { value: 'KS', text: 'Kansas' },
            { value: 'KY', text: 'Kentucky' },
            { value: 'LA', text: 'Louisiana' },
            { value: 'ME', text: 'Maine' },
            { value: 'MD', text: 'Maryland' },
            { value: 'MA', text: 'Massachusetts' },
            { value: 'MI', text: 'Michigan' },
            { value: 'MN', text: 'Minnesota' },
            { value: 'MS', text: 'Mississippi' },
            { value: 'MO', text: 'Missouri' },
            { value: 'MT', text: 'Montana' },
            { value: 'NE', text: 'Nebraska' },
            { value: 'NV', text: 'Nevada' },
            { value: 'NH', text: 'New Hampshire' },
            { value: 'NJ', text: 'New Jersey' },
            { value: 'NM', text: 'New Mexico' },
            { value: 'NY', text: 'New York' },
            { value: 'NC', text: 'North Carolina' },
            { value: 'ND', text: 'North Dakota' },
            { value: 'OH', text: 'Ohio' },
            { value: 'OK', text: 'Oklahoma' },
            { value: 'OR', text: 'Oregon' },
            { value: 'PA', text: 'Pennsylvania' },
            { value: 'RI', text: 'Rhode Island' },
            { value: 'SC', text: 'South Carolina' },
            { value: 'SD', text: 'South Dakota' },
            { value: 'TN', text: 'Tennessee' },
            { value: 'TX', text: 'Texas' },
            { value: 'UT', text: 'Utah' },
            { value: 'VT', text: 'Vermont' },
            { value: 'VA', text: 'Virginia' },
            { value: 'WA', text: 'Washington' },
            { value: 'WV', text: 'West Virginia' },
            { value: 'WI', text: 'Wisconsin' },
            { value: 'WY', text: 'Wyoming' },
        ],
        Canada: [
            { value: '', text: '--Select State--' },
            { value: 'AB', text: 'Alberta' },
            { value: 'BC', text: 'British Columbia' },
            { value: 'MB', text: 'Manitoba' },
            { value: 'NB', text: 'New Brunswick' },
            { value: 'NL', text: 'Newfoundland and Labrador' },
            { value: 'NT', text: 'Northwest Territories' },
            { value: 'NS', text: 'Nova Scotia' },
            { value: 'NU', text: 'Nunavut' },
            { value: 'ON', text: 'Ontario' },
            { value: 'PE', text: 'Prince Edward Island' },
            { value: 'QC', text: 'QuÃ©bec' },
            { value: 'SK', text: 'Saskatchewan' },
            { value: 'YT', text: 'Yukon' },
        ]
    };

    const country = document.getElementById('input_id_country_label_'+$field_id);
    const state = document.getElementById('input_id_state_label_'+$field_id);
    const selectedOption = country.value;

    // Clear the second dropdown
    state.innerHTML = '';

    // Populate the second dropdown with the new options
    options[selectedOption].forEach(option => {
        const newOption = document.createElement('option');
        newOption.value = option.value;
        newOption.text = option.text;
        state.add(newOption);
    });

}

function rmToggleOtherText(field){
    if(field.type == 'radio') {
        let other_text_field = document.getElementById(field.getAttribute("name") + '_other_input');
        if(field.value == '' && field.checked == true) {
            if(other_text_field != null) {
                other_text_field.style.display = "block";
                other_text_field.removeAttribute("disabled");
            }
        } else {
            if(other_text_field != null) {
                other_text_field.style.display = "none";
                other_text_field.setAttribute("disabled", true);
            }
        }
    } else if(field.type == 'checkbox') {
        let fieldName = field.getAttribute("name");
        fieldName = fieldName.substring(0, fieldName.length - 2);
        let other_text_field = document.getElementById(fieldName + '_other_input');
        if(field.value == '') {
            if(other_text_field != null) {
                if(field.checked == true) {
                    other_text_field.style.display = "block";
                    other_text_field.removeAttribute("disabled");
                } else {
                    other_text_field.style.display = "none";
                    other_text_field.setAttribute("disabled", true);
                }
            }
        }
    }
}

function rmInitCaptchaV2() {
    let captEls = document.getElementsByClassName('g-recaptcha');
    for(let index = 0; index < captEls.length; index++) {
        rmReCaptchas.push(grecaptcha.render(captEls[index], {'sitekey' : captEls[index].dataset.sitekey}));
    }
}