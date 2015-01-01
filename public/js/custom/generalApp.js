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