// Confirm before deleting things.

import translator from 'bazinga-translator';

function bindMessage(selector, message) {
    const translated = translator.trans(message);

    addEventListener('click', event => {
        if (event.target.closest(selector) && !confirm(translated)) {
            event.preventDefault();
        }
    });
}

bindMessage('.js-confirm-comment-delete', 'prompt.confirm_comment_delete');
bindMessage('.js-confirm-message-delete', 'prompt.confirm_message_delete');
bindMessage('.js-confirm-submission-delete', 'prompt.confirm_submission_delete');
bindMessage('.js-confirm-wiki-delete', 'prompt.confirm_wiki_delete');
