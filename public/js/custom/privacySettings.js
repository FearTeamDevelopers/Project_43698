jQuery(document).ready(function () {
    var analyticCookie = getCookie('cookies-consent-analytics');
    var functionalCookie = getCookie('cookies-consent-functional');

    if (analyticCookie != "") {
        jQuery('.privacysettings #cookies-analytics').attr('checked', 'checked');
    }
    if (functionalCookie != "") {
        jQuery('.privacysettings #cookies-functional').attr('checked', 'checked');
    }

    jQuery('.privacysettings #cookies-functional, .privacysettings #cookies-analytics').click(function () {
        var checkbox = jQuery(this);
        var cookieName = 'cookies-consent-' + checkbox.data('shortcut');

        if (checkbox.prop('checked')) {
            setCookie(cookieName, 1, 365);
        } else {
            console.log('deleting');
            deleteCookie(cookieName);
        }
    });
});
