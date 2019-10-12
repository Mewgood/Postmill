import { fadeOutAndRemove } from './lib/animation';

document.querySelectorAll('.site-alerts__alert').forEach(alertEl => {
    const dismissEl = alertEl.querySelector('.site-alerts__dismiss');

    dismissEl.onclick = () => {
        dismissEl.onclick = null;
        fadeOutAndRemove(alertEl);
    };
});
