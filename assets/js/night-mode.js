import Router from 'fosjsrouting';
import { fetch } from './lib/http';

function toggleNightMode(buttonEl, formEl) {
    document.documentElement.setAttribute('data-night-mode', buttonEl.value);

    const path = Router.generate('change_night_mode', { _format: 'json' });
    const body = new FormData(formEl);
    body.append(buttonEl.name, buttonEl.value);

    fetch(path, { body, method: 'POST' });
}

addEventListener('submit', event => {
    if (event.target.closest('.js-night-mode-form')) {
        event.preventDefault();
    }
});

addEventListener('click', event => {
    const buttonEl = event.target.closest('.js-night-mode-form button');

    if (buttonEl) {
        toggleNightMode(buttonEl, event.target.closest('.js-night-mode-form'));
    }
}, true);
