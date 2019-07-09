import $ from 'jquery';
import GraphemeSplitter from 'grapheme-splitter';
import translator from 'bazinga-translator';

let splitter;

function inputHandler() {
    if (!splitter) {
        splitter = new GraphemeSplitter();
    }

    const $this = $(this);
    const characterCount = splitter.countGraphemes($this.val());
    const maxCharacters = $this.data('max-characters');

    if (characterCount > maxCharacters) {
        const message = translator.trans('flash.too_many_characters', {
            count: characterCount,
            max: maxCharacters,
        });

        this.setCustomValidity(message);
    } else {
        this.setCustomValidity('');
    }
}

$('[data-max-characters]')
    .each(inputHandler)
    .on('input', inputHandler);
