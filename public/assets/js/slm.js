function download(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

function slm_exportlicense(){
    var license_expt_id = document.getElementById("lic-json-data");
    var filelicname = license_expt_id.getAttribute('data-lickey');
    var license_data = document.getElementById("lic-json-data").textContent;
    var text = license_data;
    var filename = "license-" + filelicname + ".json";
    download(filename, text);
}

