import $ from 'jquery';
import translator from 'bazinga-translator';

$('.js-confirm-comment-delete').click(function () {
    return confirm(translator.trans('prompt.confirm_comment_delete'));
});

$('.js-confirm-message-delete').click(function () {
    return confirm(translator.trans('prompt.confirm_message_delete'));
});

$('.js-confirm-submission-delete').click(function () {
    return confirm(translator.trans('prompt.confirm_submission_delete'));
});

$('.js-confirm-wiki-delete').click(function () {
    return confirm(translator.trans('prompt.confirm_wiki_delete'));
});
