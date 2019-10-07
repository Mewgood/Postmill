document.querySelectorAll('.site-alerts__alert').forEach(alertEl => {
    const dismissEl = alertEl.querySelector('.site-alerts__dismiss');

    dismissEl.onclick = () => {
        dismissEl.onclick = null;
        alertEl.classList.remove('site-alerts__alert');
        alertEl.offsetWidth;
        alertEl.classList.add('site-alerts__alert');
        alertEl.style.animationDirection = 'reverse';
        alertEl.onanimationend = () => alertEl.parentNode.removeChild(alertEl);
    };
});
