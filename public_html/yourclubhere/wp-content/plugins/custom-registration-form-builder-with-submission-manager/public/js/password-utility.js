(function ($) {

    $(document).ready(function () {

        function handlePasswordToggle(selector, wrapperClass, fieldClass) {
            $(selector).each(function () {
                const passwordInput = $(this);
                const closestDiv = passwordInput.closest('div');

                closestDiv.addClass(wrapperClass).parent().addClass(fieldClass);
                if (closestDiv.find('.rm-togglePassword').length === 0) {
                    closestDiv.append('<span class="rm-togglePassword"></span>');
                }

                closestDiv.find('.rm-togglePassword').on('click', function () {
                    const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                    passwordInput.attr('type', type);
                    $(this).toggleClass('rm-togglePassword-show');
                });
            });
        }


        handlePasswordToggle('.rm-login-wrapper [name="pwd"]', 'rm-password-toggle-wrap', '');
        handlePasswordToggle('#rm-form-container [name="pwd"], .rmagic form.rmagic-form .rmagic-row .rmagic-fields-wrap [name="pwd"]', 'rmform-password-toggle-wrap', 'rmform-password-field-col');
        handlePasswordToggle('#rm-form-container [name="password_confirmation"], .rmagic form.rmagic-form .rmagic-row .rmagic-fields-wrap [name="password_confirmation"]', 'rmform-c-password-toggle-wrap', 'rmform-password-field-col');
        handlePasswordToggle('#rm_reset_pass_form [name="new_pass"]', 'rm-password-toggle-wrap', '');
        handlePasswordToggle('#rm_reset_pass_form [name="new_pass_repeat"]', 'rm-password-toggle-wrap', '');



    });


})(jQuery);
