import Router from 'fosjsrouting';
import { fetch } from './lib/http';

function toggleNightMode(formEl) {
    document.documentElement.classList.toggle('light-mode');
    const isDark = document.documentElement.classList.toggle('dark-mode');

    const route = isDark ? 'night_mode_on' : 'night_mode_off';

    fetch(Router.generate(route, { _format: 'json' }), {
        body: new FormData(formEl),
        method: 'POST',
    });
}

addEventListener('submit', event => {
    const formEl = event.target.closest('.js-night-mode-form');

    if (formEl) {
        event.preventDefault();

        toggleNightMode(formEl);
    }
});
