jQuery.noConflict();

jQuery(document).ready(function () {

    jQuery('.sluzby').click(function (event) {
        event.preventDefault();
        jQuery('#dropdown').toggle('slow');
    });
    jQuery('.dropdown').click(function (event) {
        event.preventDefault();
        jQuery(this).children('ul.submenu').toggle('slow');
    });
    jQuery('#displaySearch').click(function (event) {
        event.preventDefault();
        jQuery('.searchWrapper').toggle('slow');
    });
    jQuery('.close').click(function (event) {
        event.preventDefault();
        jQuery('.searchWrapper').hide('slow');
    });
    jQuery('.closeNotif').click(function (event) {
        event.preventDefault();
        jQuery('.notificationWrapper').hide('slow');
    });

    jQuery(window).load(function () {
        jQuery('#loader, .loader').hide();

        jQuery.post('/app/system/showprofiler/', function (msg) {
            jQuery('body').append(msg);
        });
    });
    
    /* GLOBAL SCRIPTS */

    jQuery('.datepicker').datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd',
        firstDay: 1
    });


    jQuery('.ajax-button').click(function () {
        var href = jQuery(this).attr('href');
        var val = jQuery(this).val();

        jQuery('#dialog').load(href).dialog({
            title: val,
            width: '550px',
            modal: true,
            position: {my: 'center', at: 'top', of: window},
            buttons: {
                Cancel: function () {
                    jQuery(this).dialog('close');
                }
            }
        });
    });
    
    /* ---------------------- UPLOAD FORMS --------------------------------*/
    jQuery('.uploadForm .multi_upload').click(function () {
        if (jQuery('.uploadForm .file_inputs input[type=file]').length < 7) {
            jQuery('.uploadForm .file_inputs input[type=file]')
                    .last()
                    .after('<input type="file" name="uploadfile[]" accept="image/*"/>');
        }
    });

    jQuery('.uploadForm .multi_upload_dec').click(function () {
        if (jQuery('.uploadForm .file_inputs input[type=file]').length > 1) {
            jQuery('.uploadForm .file_inputs input[type=file]').last().remove();
        }
    });

    jQuery('.uploadForm').submit(function () {
        jQuery('#loader').show();
    });

    /* ---------------------- AJAX OPERATIONS --------------------------------*/
    jQuery('.ajaxDelete').click(function (event) {
        event.preventDefault();
        var parentTr = jQuery(this).parents('article');
        var url = jQuery(this).attr('href');
        var csrf = jQuery('#csrf').val();

        jQuery('#deleteDialog p').text('Opravdu chcete pokračovat v mazání?');

        jQuery('#deleteDialog').dialog({
            resizable: false,
            width: 350,
            height: 200,
            modal: true,
            buttons: {
                "Smazat": function () {
                    jQuery.post(url, {csrf: csrf}, function (msg) {
                        if (msg == 'success') {
                            parentTr.fadeOut();
                        } else {
                            alert(msg);
                        }
                    });
                    jQuery(this).dialog("close");
                },
                "Zrušit": function () {
                    jQuery(this).dialog("close");
                }
            }
        });

        return false;
    });

    jQuery('.ajaxReload').click(function () {
        event.preventDefault();
        var url = jQuery(this).attr('href');
        var csrf = jQuery('#csrf').val();

        jQuery('#deleteDialog p').text('Opravdu chcete pokračovat?');

        jQuery('#deleteDialog').dialog({
            resizable: false,
            width: 350,
            height: 200,
            modal: true,
            buttons: {
                "Ano": function () {
                    jQuery.post(url, {csrf: csrf}, function (msg) {
                        if (msg == 'success') {
                            location.reload();
                        } else {
                            alert(msg);
                        }
                    });
                },
                "Ne": function () {
                    jQuery(this).dialog("close");
                }
            }
        });
        return false;
    });

    jQuery('.ajaxChangestate').click(function () {
        var url = jQuery(this).attr('href');
        var csrf = jQuery('#csrf').val();

        jQuery.post(url, {csrf: csrf}, function (msg) {
            if (msg == 'active' || msg == 'inactive') {
                location.reload();
            } else {
                alert(msg);
            }
        });

        return false;
    });

    jQuery('#hledat').click(function (event) {
        event.preventDefault();
        jQuery('.search').submit();
    });
    jQuery('#hledatHastrman').click(function (event) {
        event.preventDefault();
        jQuery('.fulltextsearch').submit();
    });
});
/**akce**/
jQuery(function () {
    var _direction = 'left';
    jQuery('#carousel').carouFredSel({
        direction: _direction,
        responsive: true,
        circular: true,
        items: {
            width: 400,
            height: 300,
            visible: {
                min: 1,
                max: 3
            }
        },
        scroll: {
            items: 1,
            duration: 2000,
            timeoutDuration: 3500,
            pauseOnHover: 'immediate',
            onEnd: function (data) {
                _direction = (_direction == 'left') ? 'right' : 'left';
                $(this).trigger('configuration', ['direction', _direction]);
            }
        }
    });
});
/**partneři**/
jQuery(function () {
    var _direction = 'left';
    jQuery('#carousel2').carouFredSel({
        direction: _direction,
        responsive: true,
        circular: true,
        items: {
            width: 200,
            height: 150,
            visible: {
                min: 3,
                max: 6
            }
        },
        scroll: {
            items: 1,
            duration: 2000,
            timeoutDuration: 0,
            pauseOnHover: 'immediate',
            onEnd: function (data) {
                _direction = (_direction == 'left') ? 'right' : 'left';
                $(this).trigger('configuration', ['direction', _direction]);
            }
        }
    });
});