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
                jQuery(this).trigger('configuration', ['direction', _direction]);
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
                jQuery(this).trigger('configuration', ['direction', _direction]);
            }
        }
    });
});