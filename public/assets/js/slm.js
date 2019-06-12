function download(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

// document.getElementById("btn-lic-export").addEventListener("click", function () {
//     // Generate download of hello.txt file with some content
//     var license_data    = document.getElementById("lic-json-data").textContent;
//     var text            = license_data;
//     var filename        = "license.json";

//     download(filename, text);
// }, false);


function slm_exportlicense(){
    var license_data = document.getElementById("lic-json-data").textContent;
    var text = license_data;
    var filename = "license.json";
    download(filename, text);
}