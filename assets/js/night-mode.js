import $ from 'jquery';
import Router from 'fosjsrouting';

$(document).on('submit', '.js-night-mode-form', function (event) {
    event.preventDefault();

    const $root = $(':root');
    $root.toggleClass('dark-mode light-mode');

    const route = $root.hasClass('dark-mode') ? 'night_mode_on' : 'night_mode_off';

    $.ajax({
        url: Router.generate(route, { _format: 'json' }),
        data: $(this).serialize(),
        method: 'POST',
    });
});
