import $ from 'jquery';
import router from 'fosjsrouting';

$(document).on('submit', '.subscribe-form', function (event) {
    const $form = $(this);
    const forum = $form.data('forum');

    if (forum === undefined) {
        throw new Error('Missing data-forum attribute');
    }

    event.preventDefault();

    const $button = $form.find('.subscribe-button');
    const subscribe = $button.hasClass('subscribe-button--subscribe');

    $button.prop('disabled', true);

    $.ajax({
        url: router.generate(subscribe ? 'subscribe' : 'unsubscribe', {
            forum_name: forum,
            _format: 'json',
        }),
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json',
    }).done(() => {
        const proto = $button.data('toggle-prototype');

        $button
            .removeClass(`subscribe-button--${subscribe ? '' : 'un'}subscribe`)
            .addClass(`subscribe-button--${!subscribe ? '' : 'un'}subscribe`)
            .data('toggle-prototype', $button.html())
            .html(proto);
    }).always(() => {
        $button
            .prop('disabled', false)
            .blur();
    });
});
