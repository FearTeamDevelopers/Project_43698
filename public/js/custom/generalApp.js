jQuery.noConflict();


jQuery(document).ready(function () {

    jQuery('.bxslider').bxSlider();

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
});

jQuery(function () {
    var _direction = 'left';
    jQuery('#carousel').carouFredSel({
        direction: _direction,
        responsive: true,
        circular: true,
        items: {
            width: 350,
            height: 300,
            visible: {
                min: 2,
                max: 5
            }
        },
        scroll: {
            items: 1,
            duration: 1000,
            timeoutDuration: 1500,
            pauseOnHover: 'immediate',
            onEnd: function (data) {
                _direction = (_direction == 'left') ? 'right' : 'left';
                $(this).trigger('configuration', ['direction', _direction]);
            }
        }
    });
});