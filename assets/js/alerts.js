'use strict';

import $ from 'jquery';

$(document).on('click', '.site-alerts__alert', function  () {
    const $alert = $(this);

    $alert
        .removeClass('site-alerts__alert')
        .each((i, el) => el.offsetWidth)
        .addClass('site-alerts__alert')
        .css('animation-direction', 'reverse')
        .one('animationend', function () {
            $alert.remove();
        });
});
