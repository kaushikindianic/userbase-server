$( function() {

    function dataURItoBlob(dataURI) {
        // convert base64/URLEncoded data component to raw binary data held in a string
        var byteString;
        if (dataURI.split(',')[0].indexOf('base64') >= 0)
            byteString = atob(dataURI.split(',')[1]);
        else
            byteString = unescape(dataURI.split(',')[1]);

        // separate out the mime component
        var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

        // write the bytes of the string to a typed array
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        return new Blob([ia], {type:mimeString});
    }

    $('#form_input_picture').on("change", function(evt){
        var files = evt.target.files; // FileList object
        var f = files[0];
        // Only process image files.
        if (!f.type.match('image.*')) {
            alert("Select a valid image file.");
            return false;
        }

        var reader = new FileReader();
        reader.onload = (function(theFile) {
            return function(e) {
                $('.rotate').show();
                $('.buttonbox').show();
                var el = document.getElementById('croppie');
                window.croppie = new Croppie( el, {
                    viewport: {
                        width: 300,
                        height: 300,
                        type: 'square'
                    },
                    boundary: {
                        height: $('.rotate').width()-1,
                        width: $('.rotate').width()-1
                    },
                    enableOrientation: true,
                } );

                window.croppie.bind({
                    url: e.target.result,
                    orientation: 1
                });

                $('.rotate .rleft').click( function() {
                    window.croppie.rotate(parseInt(-90));
                } );

                $('.rotate .rright').click( function() {
                    window.croppie.rotate(parseInt(90));
                } );

                $('.submit-button').click( function(e) {
                    e.preventDefault();
                    var opts = {
                        type : 'canvas',
                        size: 'viewport',
                        format: 'png',
                        quality: 0
                    }
                    window.croppie.result( opts ).then( function( src ) {
                        $('#form_picture').val(  src );
                        $('#frmPicture').submit();
                    } );
                } );
            };
        })(f);

        // Read in the image file as a data URL.
        reader.readAsDataURL(f);



    });
} );
