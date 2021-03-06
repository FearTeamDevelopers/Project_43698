jQuery.noConflict();

jQuery(document).ready(function ($) {
    //menu
    jQuery('.showMenu').click(function (e) {
        e.preventDefault();
        jQuery(this).closest('.navWrapper').find('nav').slideToggle(300).toggleClass('active');
    });

    jQuery('nav>ul>li>a.dropdown').click(function (event) {
        event.preventDefault();
        if (jQuery(this).closest('li').hasClass('active')) {
            jQuery(this).closest('li').find('ul').slideUp(300, function () {
                jQuery(this).closest('li').removeClass('active');
            });
        } else if (jQuery('nav ul li').hasClass('active')) {
            jQuery('nav ul li.active').find('ul').slideUp(300).closest('li').removeClass('active');
            jQuery(this).closest('li').find('ul').slideDown(300, function () {
                jQuery(this).closest('li').addClass('active');
            });
        } else {
            jQuery(this).closest('li').find('ul').slideDown(300, function () {
                jQuery(this).closest('li').addClass('active');
            });
        }
    });

    jQuery(window).load(function () {
        jQuery.post('/app/system/showprofiler/', function (msg) {
            jQuery('body').append(msg);
        });
    });

    jQuery('.sendEmail').click(function () {
        if (jQuery('.sendEmail span').text() == "Odpovědět na Inzerát") {
            jQuery('.sendEmail span').text('Zavřít');
        } else {
            jQuery('.sendEmail span').text('Odpovědět na Inzerát');
        }
        if (jQuery('article').hasClass('arrow_box')) {
            jQuery('article').removeClass('arrow_box');
        } else {
            jQuery('article').addClass('arrow_box');
        }

        jQuery('#sendEmail').toggle('slow');
    });

    jQuery('.images a.dropdown').click(function (e) {
        e.preventDefault();
        jQuery(this).hide();
        jQuery('.thumbImage').show('slow');
    });
    jQuery('.sluzby').click(function (event) {
        event.preventDefault();
        jQuery('#dropdown').toggle('slow');
    });

    jQuery('.closeNotif').click(function (event) {
        event.preventDefault();
        jQuery('.notificationWrapper').hide('slow');
    });

    jQuery('#openEdit').click(function (event) {
        event.preventDefault();
        jQuery('#info').hide('slow');
        jQuery('#edit').show('slow');
    });
    jQuery('#closeEdit').click(function (event) {
        event.preventDefault();
        jQuery('#info').toggle('slow');
        jQuery('#edit').toggle('slow');
    });

    if (jQuery('.notificationWrapper .notification').is(':visible')) {
        setTimeout(function () {
            jQuery('.notificationWrapper .notification').hide("slow");
        }, 3000);
    }

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
                    jQuery('#dialog p').text('');
                    jQuery(this).dialog('close');
                }
            }
        });
    });

    /* ---------------------- UPLOAD FORMS --------------------------------*/
    jQuery('.uploadForm .multi_upload').click(function (event) {
        event.preventDefault();
        if (jQuery('.uploadForm .file_inputs input[type=file]').length < 3) {
            jQuery('.uploadForm .file_inputs input[type=file]')
                    .last()
                    .after('<br/><input type="file" name="uploadfile[]" accept="image/*"/>');
        }
    });

    jQuery('.uploadForm .multi_upload_dec').click(function (event) {
        event.preventDefault();
        if (jQuery('.uploadForm .file_inputs input[type=file]').length > 1) {
            jQuery('.uploadForm .file_inputs input[type=file]').last().remove();
            jQuery('.uploadForm .file_inputs br').last().remove();
        }
    });

    jQuery('.uploadForm').submit(function () {
        jQuery('#loader').show();
    });

    /* ---------------------- AJAX OPERATIONS --------------------------------*/
    //delete image in grid list
    jQuery('.ajaxDeleteImage').click(function (event) {
        event.preventDefault();
        var clicked = jQuery(this);
        var url = jQuery(this).attr('href');
        var csrf = jQuery('#csrf').val();

        jQuery('#dialog p').text('Opravdu chcete pokračovat v mazání?');

        jQuery('#dialog').dialog({
            resizable: false,
            width: 350,
            height: 200,
            modal: true,
            buttons: {
                "Smazat": function () {
                    jQuery("#loader, .loader").show();
                    jQuery.post(url, {csrf: csrf}, function (data) {
                        if (data.error == false) {
                            jQuery('#csrf').val(data.csrf);
                            clicked.parent('span').hide('explode', 500);
                        } else {
                            jQuery('#dialog p').text(data.message);
                        }
                        jQuery("#loader, .loader").hide();
                    });
                    jQuery(this).dialog("close");
                },
                "Zrušit": function () {
                    jQuery('#dialog p').text('');
                    jQuery(this).dialog("close");
                }
            }
        });
        return false;
    });

    //delete individual row
    jQuery('.ajaxDelete').click(function (event) {
        event.preventDefault();
        var parentTr = jQuery(this).parents('tr');
        var url = jQuery(this).attr('href');
        var csrf = jQuery('#csrf').val();

        jQuery('#dialog p').text('Opravdu chcete pokračovat v mazání?');

        jQuery('#dialog').dialog({
            resizable: false,
            width: 320,
            modal: true,
            buttons: {
                "Smazat": function () {
                    jQuery("#loader, .loader").show();
                    jQuery.post(url, {csrf: csrf}, function (data) {
                        if (data.error == false) {
                            jQuery('#csrf').val(data.csrf);
                            jQuery("#loader, .loader").hide();
                            parentTr.fadeOut();
                        } else {
                            jQuery('#dialog p').text(data.message);
                        }
                    });
                    jQuery(this).dialog("close");
                },
                "Zrušit": function () {
                    jQuery('#dialog p').text('');
                    jQuery(this).dialog("close");
                }
            }
        });

        return false;
    });

    jQuery('.ajaxReload').click(function (event) {
        event.preventDefault();
        var url = jQuery(this).attr('href');
        var csrf = jQuery('#csrf').val();

        jQuery('#dialog p').text('Opravdu chcete pokračovat?');

        jQuery('#dialog').dialog({
            resizable: false,
            width: 320,
            modal: true,
            buttons: {
                "Ano": function () {
                    jQuery("#loader, .loader").show();
                    jQuery.post(url, {csrf: csrf}, function (data) {
                        if (data.error == false) {
                            jQuery('#csrf').val(data.csrf);
                            location.reload();
                        } else {
                            jQuery('#dialog p').text(data.message);
                        }
                    });
                },
                "Ne": function () {
                    jQuery('#dialog p').text('');
                    jQuery(this).dialog("close");
                }
            }
        });
        return false;
    });

    //activate/deactivate
    jQuery('.ajaxChangestate').click(function (event) {
        event.preventDefault();
        var url = jQuery(this).attr('href');
        var csrf = jQuery('#csrf').val();

        jQuery("#loader, .loader").show();
        jQuery.post(url, {csrf: csrf}, function (data) {
            if (data.error == false) {
                jQuery('#csrf').val(data.csrf);
                location.reload();
            } else {
                jQuery('#dialog p').text(data.message);

                jQuery('#dialog').dialog({
                    resizable: false,
                    width: 320,
                    modal: true,
                    buttons: {
                        "Zavřít": function () {
                            jQuery('#dialog p').text('');
                            jQuery(this).dialog("close");
                        }
                    }
                });
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
    
    jQuery('a#delete-account').click(function (event) {
        var confirmation = prompt('Potvrďte smazání účtu slovy: "Smazat účet"');

        if (confirmation !== null && confirmation === 'Smazat účet') {
            jQuery('#dialog p').text('Účet bude smazán');

            jQuery('#dialog').dialog({
                resizable: false,
                width: 320,
                modal: true,
                buttons: {
                    "Zavřít": function () {
                        jQuery('#dialog p').text('');
                        jQuery(this).dialog("close");
                    }
                }
            });
        } else {
            jQuery('#dialog p').text('Potvrzení není platné');

            jQuery('#dialog').dialog({
                resizable: false,
                width: 320,
                modal: true,
                buttons: {
                    "Zavřít": function () {
                        jQuery('#dialog p').text('');
                        jQuery(this).dialog("close");
                    }
                }
            });
            event.preventDefault();
        }
    });


    var search2 = $('#s2');
    var icon2 = $('#search2btn');

    // handling the focus event on input2
    $(search2).on('focus', function () {
        $(this).animate({
            width: '100%'
        }, 400, function () {
            // callback function
        });
        $(icon2).animate({
            right: '10px'
        }, 400, function () {
            // callback function
        });
    });

    $(search2).on('blur', function () {
        if (search2.val() == '') {
            $(search2).animate({
                width: '45%'
            }, 400, function () { });

            $(icon2).animate({
                right: '360px'
            }, 400, function () { });
        }
    });

});
