jQuery('.cookies-consent #cookie-ok').click(function (event) {
    event.preventDefault();
    var _paq = _paq || [];

    if (jQuery('.cookies-consent #cookies-analytics').prop('checked')) {
        _paq.push(['rememberConsentGiven']);
        _paq.push(['setConsentGiven']);
        setCookie('cookies-consent', 1, 7);
    } else {
        _paq.push(['forgetConsentGiven']);
        deleteCookie('cookies-consent');
    }

    jQuery('.cookies-consent').hide();
});
