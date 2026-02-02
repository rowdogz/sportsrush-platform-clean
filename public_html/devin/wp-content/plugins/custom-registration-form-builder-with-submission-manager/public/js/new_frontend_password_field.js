jQuery(document).ready(function() {
    jQuery("input[name='pwd']").password({
        shortPass: rm_pass_warnings_new.shortPass,
        badPass:rm_pass_warnings_new.badPass,
        goodPass:rm_pass_warnings_new.goodPass,
        strongPass: rm_pass_warnings_new.strongPass,
        // showPercent: false,
        animate: true,
        animateSpeed: 'fast',
        useColorBarImage: true, // use the (old) colorbar image
        customColorBarRGB: {
            red: [0, 240],
            green: [0, 240],
            blue: 10,
        } // set custom rgb color ranges for colorbar.
    });
});