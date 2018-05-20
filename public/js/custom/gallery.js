// DROPZONE
jQuery.noConflict();

jQuery(document).ready(function () {
    var simpleDialog = jQuery('#dialog').dialog({
        autoOpen: false,
        resizable: false,
        width: 320,
        modal: true,
        title: 'Chyba',
        buttons: {
            "Zavřít": function () {
                jQuery('#dialog p').text('');
                jQuery(this).dialog("close");
            }
        }
    });

    jQuery('#dropzoneProcess').addClass('nodisplay');
    dropzoneError = false;

    Dropzone.autoDiscover = false;

    dropzone = new Dropzone('form.dropzone', {
        url: '/admin/gallery/upload/',
        paramName: 'file', // The name that will be used to transfer the file
        maxFilesize: 18, // MB
        maxFiles: 10,
        acceptedFiles: 'image/*',
        addRemoveLinks: true,
        createImageThumbnails: true,
        autoProcessQueue: false,
        init: function () {
            this.on('addedfile', function () {
                jQuery('#dropzoneProcess').removeClass('nodisplay');
            });
            this.on('removedfile', function () {
                var queuedFiles = dropzone.getQueuedFiles();

                if (queuedFiles.length === 0) {
                    jQuery('#dropzoneProcess').addClass('nodisplay');
                }
            });
            this.on('maxfilesexceeded', function (file) {
                simpleDialog.text('Maximalni pocet souboru je 10');
                simpleDialog.dialog('open');
            });
            this.on('sending', function () {
                jQuery("#loader, .loader").show();
            });
            this.on('error', function () {
                dropzoneError = true;
            });
            this.on('success', function (file, response) {
                var queuedFiles = dropzone.getQueuedFiles();

                if (queuedFiles.length === 0 && dropzoneError === false) {
                    location.reload();
                } else {
                    dropzone.processQueue();
                }

                this.removeFile(file);
            });
            this.on('queuecomplete', function (file, response) {
                var queuedFiles = dropzone.getQueuedFiles();

                if (queuedFiles.length === 0 && dropzoneError === true) {
                    simpleDialog.text('Během nahrávání fotek se vyskytla chyba. Pokud se nezobrazují nahrané fotky stiskněte F5.');
                    simpleDialog.dialog('open');
                    jQuery("#loader, .loader").hide();
                }
            });
        }
    });

    jQuery('#dropzoneProcess').click(function () {
        dropzone.processQueue();
    });
});