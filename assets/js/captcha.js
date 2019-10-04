import $ from 'jquery';

$('.js-captcha-reload')
    .prop('disabled', false)
    .click(function () {
        const $image = $(this).find('img');
        const [url, queryString] = $image.attr('src').split('?', 2);

        const params = new URLSearchParams(queryString);
        params.set('n', '' + new Date().getTime());

        $image.attr('src', url + '?' + params.toString());
    });
