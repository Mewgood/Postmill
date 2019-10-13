import routing from 'fosjsrouting';
import { ok } from './lib/http';

const FETCH_SELECTOR = '.auto-fetch-submission-titles .fetch-title';

function handleBlur(el) {
    const receiverEl = document.querySelector('.receive-title');
    const url = el.value.trim();

    if (receiverEl.value.trim() === '' && /^https?:\/\//.test(url)) {
        fetch(routing.generate('fetch_title'), {
            method: 'POST',
            body: new URLSearchParams({ url }),
            credentials: 'same-origin',
        })
            .then(response => ok(response))
            .then(response => response.json())
            .then(data => {
                if (receiverEl.value.trim() === '') {
                    receiverEl.value = data.title;
                }
            });
    }
}

addEventListener('focusout', event => {
    const el = event.target.closest(FETCH_SELECTOR);

    if (el) {
        handleBlur(el);
    }
});
