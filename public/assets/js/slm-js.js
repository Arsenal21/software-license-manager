jQuery(document).ready(function($) {
    $('#slm_licenses_table .collapse').on('show.bs.collapse', function () {
        $('#slm_licenses_table .collapse.in').collapse('hide');
    });
});