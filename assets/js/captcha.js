document.querySelectorAll('.js-captcha-reload').forEach(el => {
    el.disabled = false;
    el.onclick = () => {
        const imageEl = el.querySelector('img');
        const [url, queryString] = imageEl.src.split('?', 2);

        const params = new URLSearchParams(queryString);
        params.set('n', '' + new Date().getTime());

        imageEl.src = url + '?' + params.toString();
    };
});
