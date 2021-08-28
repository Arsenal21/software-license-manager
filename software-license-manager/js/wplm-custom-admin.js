jQuery(document).ready(function ($) {
	//Add date picker listener on date fields
	if ($.fn.datepicker) {
		$('.wplm_pick_date').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	}

	//Add other admin side only jquery code below

});