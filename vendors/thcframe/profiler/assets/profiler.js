jQuery.noConflict();
jQuery(document).ready(function () {
    jQuery('.profiler-show').click(function () {
        var key = jQuery(this).attr('value');
        var target = jQuery(this).attr('data-rel');
        jQuery('#' + key + '_' + target).toggle();
    });

    jQuery('.sub-data-table tr td.backtrace').click(function () {
        var a = jQuery(this).css('height');
        var b = a.replace('px', '');
        if (b >= 250) {
            jQuery(this).parent('tr').css('height', '40px');
        } else {
            jQuery(this).parent('tr').css('height', '300px');
        }
        jQuery(this).children('div').toggle();
    });
});