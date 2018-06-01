jQuery(document).ready(function () {
    var _paq = _paq || [];
    var analyticCookie = getCookie('cookies-consent-analytics');
    var functionalCookie = getCookie('cookies-consent-functional');

    if (analyticCookie !== '') {
        jQuery('.privacysettings #cookies-analytics').attr('checked', 'checked');
    }
    if (functionalCookie !== '') {
        jQuery('.privacysettings #cookies-functional').attr('checked', 'checked');
    }

    jQuery('.privacysettings #cookies-functional, .privacysettings #cookies-analytics').click(function () {
        var checkbox = jQuery(this);
        var type = checkbox.data('shortcut');
        var cookieName = 'cookies-consent-' + type;

        if (checkbox.prop('checked')) {
            if (type === 'analytics') {
                _paq.push(['rememberConsentGiven']);
                _paq.push(['setConsentGiven']);
            }

            setCookie(cookieName, 1, 365);
        } else {
            if (type === 'analytics') {
                _paq.push(['forgetConsentGiven']);
                deleteCookie('cookies-consent-analytics');
            }
            console.log('deleting');
            deleteCookie(cookieName);
        }
    });
});
