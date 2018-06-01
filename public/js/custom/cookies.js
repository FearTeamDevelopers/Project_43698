jQuery('.cookies-consent #cookie-ok').click(function (event) {
    event.preventDefault();
    var _paq = _paq || [];

    setCookie('cookies-consent-mandatory', 1, 365);
    setCookie('cookies-consent-functional', 1, 365);

    _paq.push(['rememberConsentGiven']);
    _paq.push(['setConsentGiven']);
    setCookie('cookies-consent-analytics', 1, 365);

    jQuery('.cookies-consent').hide();
});
