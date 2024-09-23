window.addEventListener('DOMContentLoaded', (event) => {
    function SetCookieConsent(name, value, options) {
        const opts = {
            path: "/",
            ...options
        }
        if (navigator.cookieEnabled) {
            const cookieName = encodeURIComponent(name);
            const cookieVal = encodeURIComponent(value);
            let cookieText = cookieName + "=" + cookieVal;
            if (opts.days && typeof opts.days === "number") {
                const date = new Date();
                date.setTime(date.getTime() + (opts.days * 24 * 60 * 60 * 1000));
                cookieText += "; expires=" + date.toUTCString();
            }
            if (opts.path) {
                cookieText += "; path=" + opts.path;
            }
            if (opts.domain) {
                cookieText += "; domain=" + opts.domain;
            }
            cookieText += "; Cache-Control=" + "no-cache";
            document.cookie = cookieText;
        }
    }

    function SetCookieConsentOnClick() {
        const button = document.querySelector('.extra-cookie-accept-button');
        if (button != null) {
            let expireTime = button.getAttribute('data-expire');
            if (typeof expireTime !== 'string') {
                expireTime = 30;
            }
            button.addEventListener('click', function(event) {
                event.preventDefault();
                SetCookieConsent("ex-cookies-accepted", "yes", { days: parseInt(expireTime), path: "/" });
                document.querySelector('.cookie-box').classList.add('cookie-hidden');
            });
        }
    }

    function GetCookie(name) {
        if (document.cookie !== "") {
            const cookies = document.cookie.split(/; */);
            for (let cookie of cookies) {
                const [cookieName, cookieVal] = cookie.split("=");
                if (cookieName === decodeURIComponent(name)) {
                    return decodeURIComponent(cookieVal);
                }
            }
        }
        return undefined;
    }

    function ShowCookieConsent() {
        const YesCookie = GetCookie("ex-cookies-accepted");
        if (YesCookie != 'yes') {
            document.querySelector('.cookie-box').classList.remove('cookie-hidden');
        }
    }
    
    ShowCookieConsent();
    SetCookieConsentOnClick();
});
