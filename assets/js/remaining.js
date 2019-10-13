import translator from 'bazinga-translator';

let splitter;

async function handleInput(el) {
    if (!el.value.length) {
        return;
    }

    const { default: GraphemeSplitter } = await import('grapheme-splitter');

    if (!splitter) {
        splitter = new GraphemeSplitter();
    }

    const characterCount = splitter.countGraphemes(el.value);
    const maxCharacters = el.getAttribute('data-max-characters');

    if (characterCount > maxCharacters) {
        const message = translator.trans('flash.too_many_characters', {
            count: characterCount,
            max: maxCharacters,
        });

        el.setCustomValidity(message);
    } else {
        el.setCustomValidity('');
    }
}

addEventListener('input', event => {
    const el = event.target.closest('[data-max-characters]');

    if (el) {
        handleInput(el);
    }
});
