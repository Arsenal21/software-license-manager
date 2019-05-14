jQuery(document).ready(function($) {
    $('#slm_licenses_table .collapse').on('show.bs.collapse', function () {
        $('#slm_licenses_table .collapse.in').collapse('hide');
    });
});

function download(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

// Start file download.
document.getElementById("export-lic-key").addEventListener("click", function () {
    // Generate download of hello.txt file with some content
    var license_data    = this.getAttribute('data-licdata');
    var text            = license_data;
    var filename        = "license.json";
    download(filename, text);
}, false);

