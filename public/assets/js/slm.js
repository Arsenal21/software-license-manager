jQuery().ready(function() {
    //jQuery(".slm_license_form").validate();
    jQuery(".slm_license_form").validate({
        ignore: [],
        onsubmit: true,
        // onfocusout: true,
        invalidHandler: function(event, validator) {
            // 'this' refers to the form
            var errors = validator.numberOfInvalids();
            if (errors) {
                var message = errors == 1
                ? 'You missed 1 field. It has been highlighted'
                : 'You missed ' + errors + ' fields. They have been highlighted';
              jQuery("div.error_slm span").html(message);
              jQuery("div.error_slm").show();
            }
            else {
                jQuery("div.error_slm").hide();
            }
        },
        errorClass: "invalid",
        validClass: "success",
        errorContainer: "#error_box",
        errorElement: "div",
        wrapper: "div",
        errorLabelContainer: "#error_box",
        rules: {
            license_key: "required",
            first_name: "required",
            last_name: "required",
            email: {
                required: true,
                email: true
            }
        },
        messages: {
            license_key: "Please specify your license key",
            first_name: "Please specify your first name",
            last_name: "Please specify your last name",
            email: {
                required: "We need your email address.",
                email: "Your email address must be in the format of name@domain.com"
            }
        }
    });
});