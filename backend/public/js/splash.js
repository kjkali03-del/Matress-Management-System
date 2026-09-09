(function () {
    'use strict';

    var splash = document.querySelector('[data-startup-splash]');

    if (!splash) {
        return;
    }

    var finishSplash = function () {
        splash.classList.add('is-leaving');
        window.setTimeout(function () {
            splash.remove();
        }, 750);
    };

    window.setTimeout(finishSplash, 2800);
})();