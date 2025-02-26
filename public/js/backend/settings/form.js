$(function () {
    $('.select2').select2();

    $(document).on('click', '#generate-key', function (e) {
        e.preventDefault();
        $('#key').val(generateNewKey(40))
    });

    // preview and validate for logo, limit size 5MB
    var _URL = window.URL || window.webkitURL;
    $("input:file[name='logo']").change(function (e) {
        e.preventDefault();
        $preview = $('#' + e.target.name + '_preview');
        var file, img, reader;
        var maxWidth = $(this).attr('data-max-width');
        var maxHeight = $(this).attr('data-max-height');

        // check if logo file is selected or not in file selection dialog
        if (e.target.files[0]) {
            file = e.target.files[0],
                reader = new FileReader();

            // file size check
            if ((file.size / 1024) / 1024 > 5) {
                // over file size
                alert('The upper limit of files that can be attached is 5 MB.');
                cancelLogo();
            } else {
                // check logo width and height
                img = new Image();
                img.src = _URL.createObjectURL(file);

                // preview
                reader.onload = (function (file) {
                    return function (e) {
                        $preview.empty();
                        $preview.append($('<img>').attr({
                            src: e.target.result,
                            width: '200px',
                            title: file.name,
                            class: 'img-circle elevation-2'
                        }));
                        $preview.next('p').addClass('show');
                    };
                })(file);
                reader.readAsDataURL(file);
            }
        } else {
            // open file select model and not selected
            cancelLogo();
        }

        // delete preview and value to empty
        function cancelLogo() {
            $preview.empty();
            $('[name="' + e.target.name + '"]').val('');
            $preview.next('.delete-logo-preview').removeClass('show');
            return false;
        }
    });
});

function generateNewKey(length) {
    var result = '';
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

function deleteLogoPreview(element) {
    $(element).parent('.logo-preview-area').prevAll('input').val('');
    // $(element).prev('div').html('');
    $('#logo_preview img.img-circle').attr("src", baseUrl + "/img/logo.png");
    $(element).next('input').val(1);
    $(element).removeClass('show');
}
