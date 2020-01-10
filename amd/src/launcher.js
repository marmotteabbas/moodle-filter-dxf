/*
 * Released under MIT License (MIT)
 * Made by bzuillsmith ; bzuillsmith@gmail.com
*/
define(['jquery','filter_dxf/three','filter_dxf/dxf-parser','filter_dxf/three-dxf'],
function($, THREE, DxfParser, ThreeDxf) {
        var progress = document.getElementById('file-progress-bar-1');
        var $progress = $('.progress');
        var indice = 1;
        var all_urls = [];
        var wwwroot = "";

    var onSuccess = function(evt){
        var fileReader = evt.target;
        if(fileReader.error) {
            return alert("error onloadend!?");
        }
        progress.style.width = '100%';
        progress.textContent = '100%';
        setTimeout(function() { $progress.removeClass('loading'); }, 2000);
        var parser = new DxfParser();
        var dxf = parser.parseSync(fileReader.result);

        // Three.js changed the way fonts are loaded, and now we need to use FontLoader to load a font
        //  and enable TextGeometry. See this example http://threejs.org/examples/?q=text#webgl_geometry_text
        //  and this discussion https://github.com/mrdoob/three.js/issues/7398 
        var font;
        var loader = new THREE.FontLoader();
        loader.load( wwwroot+"/filter/dxf/fonts/helvetiker_regular.typeface.json", function ( response ) {
            font = response;
            ThreeDxf.Viewer(dxf, document.getElementById('cad-view-'+indice), 400, 400, font);
            indice++;

            if(all_urls.length >= indice) {
            launch();
            }
        });
    };

    var updateProgress = function(evt) {
       // console.log('progress');
       // console.log(Math.round((evt.loaded /evt.total) * 100));
        if(evt.lengthComputable) {
            var percentLoaded = Math.round((evt.loaded /evt.total) * 100);
            if (percentLoaded < 100) {
                progress.style.width = percentLoaded + '%';
                progress.textContent = percentLoaded + '%';
            }
        }
    };

    var errorHandler = function(evt) {
        switch(evt.target.error.code) {
        case evt.target.error.NOT_FOUND_ERR:
            alert('File Not Found!');
            break;
        case evt.target.error.NOT_READABLE_ERR:
            alert('File is not readable');
            break;
        case evt.target.error.ABORT_ERR:
            break; // noop
        default:
            alert('An error occurred reading this file.');
        }
    };

    var abortUpload = function() {
        alert('Aborted read!');
     };

    var launch = function() {
            progress = document.getElementById('file-progress-bar-'+indice);
            var xhr = new XMLHttpRequest();
            xhr.open('GET', all_urls[indice-1], true);
            xhr.responseType = 'blob';
            xhr.onload = function() {
            if (this.status == 200) {
                var myBlob = this.response;
                    progress.style.width = '0%';
                    progress.textContent = '0%';

                    var file = new File([myBlob], all_urls[indice-1],
                    { type: "application/octet-binary", lastModified: Date.now() });
                    var output = [];
                    output.push('<li><strong>', encodeURI(file.name), '</strong> (', file.type || 'n/a', ') - ',
                        file.size, ' bytes, last modified: ',
                        file.lastModifiedDate ? file.lastModifiedDate.toLocaleDateString() : 'n/a',
                        '</li>');

                    $progress.addClass('loading');

                    var reader = new FileReader();
                    reader.onprogress = updateProgress;
                    reader.onloadend = onSuccess;
                    reader.onabort = abortUpload;
                    reader.onerror = errorHandler;
                    reader.readAsText(file);
            }
         };
         xhr.send();
    };

    return {
        init: function(urls, root) {
            wwwroot = root;
            all_urls = urls;
            launch();
        }
    };
});