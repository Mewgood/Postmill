import $ from 'jquery';
import router from 'fosjsrouting';
import { ok } from './lib/http';

$(document).on('submit', '.subscribe-form', function (event) {
    const $form = $(this);
    const forum = $form.data('forum');

    if (forum === undefined) {
        throw new Error('Missing data-forum attribute');
    }

    event.preventDefault();

    const $button = $form.find('.subscribe-button');
    const subscribe = $button.hasClass('subscribe-button--subscribe');

    $button.prop('disabled', true);

    const url = router.generate(subscribe ? 'subscribe' : 'unsubscribe', {
        forum_name: forum,
        _format: 'json',
    });

    fetch(url, {
        method: 'POST',
        body: new FormData($form[0]),
        credentials: 'same-origin',
    })
        .then(response => ok(response))
        .then(() => {
            const proto = $button.data('toggle-prototype');

            $button
                .toggleClass('subscribe-button--subscribe subscribe-button--unsubscribe')
                .data('toggle-prototype', $button.html())
                .html(proto);
        })
        .finally(() => (
            $button
                .prop('disabled', false)
                .blur()
        ));
});
