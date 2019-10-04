import $ from 'jquery';
import routing from 'fosjsrouting';
import { ok } from './lib/http';

$('.auto-fetch-submission-titles .fetch-title').blur(function () {
    const $receiver = $('.receive-title');
    const url = $(this).val().trim();

    if ($receiver.val().trim() === '' && /^https?:\/\//.test(url)) {
        fetch(routing.generate('fetch_title'), {
            method: 'POST',
            body: new URLSearchParams({ url }),
            credentials: 'same-origin',
        })
            .then(response => ok(response))
            .then(response => response.json())
            .then(data => {
                if ($receiver.val().trim() === '') {
                    $('.receive-title').val(data.title);
                }
            });
    }
});
