import { debounce } from 'lodash-es';
import routing from 'fosjsrouting';
import translator from 'bazinga-translator';
import { escapeHtml, parseHtml } from './lib/html';
import { fetch, ok } from './lib/http';
import { highlightRoot } from './syntax';

const DEBOUNCE_RATE = 600;

function makePreview(renderedHtml) {
    const preview = parseHtml(`
        <h3 class="markdown-preview__title">
            ${escapeHtml(translator.trans('markdown_type.preview'))}
        </h3>
        <div class="markdown-preview__inner">${renderedHtml}</div>
    `);

    highlightRoot(preview);

    return preview;
}

function handleInput(el) {
    fetch(routing.generate('markdown_preview'), {
        method: 'POST',
        body: el.value,
        headers: { 'Content-Type': 'text/html; charset=UTF-8' },
    })
        .then(response => ok(response))
        .then(response => response.text())
        .then(renderedHtml => {
            const target = document.getElementById(el.id + '_preview');
            target.innerHTML = '';

            if (renderedHtml.trim().length > 0) {
                target.append(makePreview(renderedHtml));
            }
        });
}

const inputHandlerMap = new WeakMap();

addEventListener('input', event => {
    const el = event.target.closest('.js-markdown-preview');

    if (el && !inputHandlerMap.has(el)) {
        inputHandlerMap.set(el, debounce(() => handleInput(el), DEBOUNCE_RATE));
    }

    if (el) {
        inputHandlerMap.get(el)();
    }
});
