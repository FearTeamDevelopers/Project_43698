jQuery('.cookies-consent #cookie-ok').click(function (event) {
    event.preventDefault();
    var _paq = _paq || [];

    if (jQuery('.cookies-consent #cookies-mandatory').prop('checked')) {
        setCookie('cookies-consent-mandatory', 1, 365);
    }

    if (jQuery('.cookies-consent #cookies-functional').prop('checked')) {
        setCookie('cookies-consent-functional', 1, 365);
    } else {
        deleteCookie('cookies-consent-functional');
    }

    if (jQuery('.cookies-consent #cookies-analytics').prop('checked')) {
        _paq.push(['rememberConsentGiven']);
        _paq.push(['setConsentGiven']);
        setCookie('cookies-consent-analytics', 1, 365);
    } else {
        _paq.push(['forgetConsentGiven']);
        deleteCookie('cookies-consent-analytics');
    }

    jQuery('.cookies-consent').hide();
});
