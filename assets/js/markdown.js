import { debounce } from 'lodash';
import routing from 'fosjsrouting';
import translator from 'bazinga-translator';
import $ from 'jquery';

function createPreview() {
    const $input = $(this);

    $.ajax({
        url: routing.generate('markdown_preview'),
        method: 'POST',
        dataType: 'html',
        data: { markdown: $input.val() },
    }).done(content => {
        const html = content.length > 0
            ? `<h3 class="markdown-preview__title">${translator.trans('markdown_type.preview')}</h3>
               <div class="markdown-preview__inner">${content}</div>`
            : '';

        $('#' + $input.attr('id') + '_preview').html(html);
    });
}

$(() => $(document).on('input', '.js-markdown-preview', debounce(createPreview, 600)));
