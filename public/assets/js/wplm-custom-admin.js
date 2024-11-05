jQuery(document).ready(function($){
    //Add date picker listener on date fields
    if ($.fn.datepicker){
        jQuery('.wplm_pick_date').datepicker({
            dateFormat : 'yy-mm-dd'
        });
    }
});


