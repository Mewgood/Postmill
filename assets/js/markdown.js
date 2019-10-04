import { debounce } from 'lodash';
import routing from 'fosjsrouting';
import translator from 'bazinga-translator';
import $ from 'jquery';
import { ok } from './lib/http';

function createPreview() {
    const $input = $(this);

    fetch(routing.generate('markdown_preview'), {
        method: 'POST',
        headers: { 'Content-Type': 'text/html; charset=UTF-8' },
        credentials: 'same-origin',
        body: $input.val(),
    })
        .then(response => ok(response))
        .then(response => response.text())
        .then(content => {
            const html = content.length > 0
                ? `<h3 class="markdown-preview__title">${translator.trans('markdown_type.preview')}</h3>
                  <div class="markdown-preview__inner">${content}</div>`
                : '';

            $('#' + $input.attr('id') + '_preview').html(html);
        });
}

$(() => $(document).on('input', '.js-markdown-preview', debounce(createPreview, 600)));
