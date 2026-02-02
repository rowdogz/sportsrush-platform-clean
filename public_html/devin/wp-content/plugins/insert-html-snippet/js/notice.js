	jQuery(document).ready(function() {
		jQuery('#xyz_ihs_system_notice_area').animate({
			opacity : 'show',
			height : 'show'
		}, 500);

		jQuery('#xyz_ihs_system_notice_area_dismiss').click(function() {
			jQuery('#xyz_ihs_system_notice_area').animate({
				opacity : 'hide',
				height : 'hide'
			}, 500);

		});
		let ihs_deactivateURL = '';

		jQuery(document).on('click', '.xyz-ihs-deactivate-link', function(e) {
			e.preventDefault();
			ihs_deactivateURL = jQuery(this).attr('href');
			jQuery('#xyz-ihs-modal').fadeIn();
		});
		jQuery('#xyz-ihs-proceed-deactivate').on('click', function() {
			window.location.href = ihs_deactivateURL;
		});
		jQuery('#xyz-ihs-cancel-deactivate').on('click', function() {
			jQuery('#xyz-ihs-modal').fadeOut();
		});
	});
