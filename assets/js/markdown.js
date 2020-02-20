import { debounce } from 'lodash-es';
import translator from 'bazinga-translator';
import { escapeHtml, parseHtml } from './lib/html';
import { highlightRoot } from './syntax';

const DEBOUNCE_RATE = 600;

let parser;

async function loadParser() {
    const { default: md } = await import('markdown-it');
    parser = parser || md();

    return Promise.resolve(parser);
}

function makePreview(renderedHtml) {
    return parseHtml(`
        <h3 class="markdown-preview__title">
            ${escapeHtml(translator.trans('markdown.preview'))}
        </h3>
        <div class="markdown-preview__inner">${renderedHtml}</div>
    `);
}

async function handleInput(el) {
    const parser = await loadParser();
    const target = document.getElementById(el.id + '_preview');
    target.innerHTML = '';

    const renderedHtml = parser.render(el.value);
    if (renderedHtml.trim().length > 0) {
        const preview = makePreview(renderedHtml);
        highlightRoot(preview);

        target.append(preview);
    }
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
