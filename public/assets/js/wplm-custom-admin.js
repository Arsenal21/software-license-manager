jQuery(document).ready(function($){
    //Add date picker listener on date fields
    if ($.fn.datepicker){
        jQuery('.wplm_pick_date').datepicker({
            dateFormat : 'yy-mm-dd'
        });
    }
});

jQuery(document).ready(function ($) {
    if (jQuery("body").hasClass("plugins-php")) {
        document.querySelector('[data-slug="software-license-manager"] a').addEventListener('click', function (event) {
            event.preventDefault()
            var urlRedirect = document.querySelector('[data-slug="software-license-manager"] a').getAttribute('href');
            if (confirm('Are you sure you want to disable this plugin?')) {
                window.location.href = urlRedirect;
            } else {
                console.log('Ohhh, you are so sweet!')
            }
        })
    }
});