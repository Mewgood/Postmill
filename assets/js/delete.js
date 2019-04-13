'use strict';

import $ from 'jquery';
import Translator from 'bazinga-translator';

$(function () {
    $('.js-confirm-comment-delete').click(function () {
        return confirm(Translator.trans('prompt.confirm_comment_delete'));
    });

    $('.js-confirm-message-delete').click(() => {
        return confirm(Translator.trans('prompt.confirm_message_delete'));
    });

    $('.js-confirm-submission-delete').click(function () {
        return confirm(Translator.trans('prompt.confirm_submission_delete'));
    });

    $('.js-confirm-wiki-delete').click(function () {
        return confirm(Translator.trans('prompt.confirm_wiki_delete'));
    });
});
