import $ from 'jquery';

$(document).on('click', '.site-alerts__dismiss', function () {
    const $alert = $(this).parents('.site-alerts__alert');

    $alert
        .removeClass('site-alerts__alert')
        .each((i, el) => el.offsetWidth)
        .addClass('site-alerts__alert')
        .css('animation-direction', 'reverse')
        .one('animationend', () => $alert.remove());
});
