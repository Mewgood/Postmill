import router from 'fosjsrouting';
import translator from 'bazinga-translator';
import { fetch, ok } from './lib/http';
import { formatNumber } from './lib/intl';

const REAL_LABEL = '.subscribe-button__label-text';
const FAKE_LABEL = '.subscribe-button__dummy-label';
const COUNT_LABEL = '.subscribe-button__subscriber-count';

class SubscribeButton {
    constructor(formEl) {
        this.formEl = formEl;
        this.buttonEl = formEl.querySelector('.subscribe-button');
        this.subscribed = this.buttonEl.classList.contains('subscribe-button--unsubscribe');
        this.subscriberCount = Number(this.buttonEl.getAttribute('data-subscriber-count'));
        this.loading = false;

        this.handleSubmit = this.handleSubmit.bind(this);
        formEl.addEventListener('submit', this.handleSubmit);
    }

    get submitUrl() {
        const forum = this.formEl.getAttribute('data-forum');

        return router.generate(this.subscribed ? 'unsubscribe' : 'subscribe', {
            forum_name: forum,
            _format: 'json',
        });
    }

    handleSubmit() {
        if (this.loading) {
            return;
        }

        this.loading = true;
        this.updateView();

        fetch(this.submitUrl, {
            method: 'POST',
            body: new FormData(this.formEl),
        })
            .then(response => ok(response))
            .then(() => {
                this.subscribed = !this.subscribed;
                this.subscriberCount += this.subscribed ? 1 : -1;
            })
            .catch(() => {
                this.formEl.removeEventListener('submit', this.handleSubmit);
                this.formEl.submit();
            })
            .finally(() => {
                this.loading = false;
                this.updateView();
            });
    }

    updateView() {
        this.buttonEl.disabled = this.loading;
        this.buttonEl.setAttribute('data-subscriber-count', this.subscriberCount);

        if (this.subscribed) {
            this.buttonEl.classList.remove('subscribe-button--subscribe');
            this.buttonEl.classList.add('subscribe-button--unsubscribe');
        } else {
            this.buttonEl.classList.remove('subscribe-button--unsubscribe');
            this.buttonEl.classList.add('subscribe-button--subscribe');
        }

        this.buttonEl.querySelector(REAL_LABEL).innerText = this.subscribed
            ? translator.trans('action.unsubscribe')
            : translator.trans('action.subscribe');

        this.buttonEl.querySelector(FAKE_LABEL).innerText = this.subscribed
            ? translator.trans('action.subscribe')
            : translator.trans('action.unsubscribe');

        const countEl = this.buttonEl.querySelector(COUNT_LABEL);
        countEl.innerText = formatNumber(this.subscriberCount);
        countEl.setAttribute('aria-label', translator.transChoice(
            'forum.subscriber_count',
            this.subscriberCount,
            { formatted_count: formatNumber(this.subscriberCount) }
        ));
    }
}

const subscribeObjectMap = new WeakMap();

addEventListener('click', event => {
    const buttonEl = event.target.closest('.subscribe-button');

    if (buttonEl) {
        event.preventDefault();
        buttonEl.blur();

        const formEl = buttonEl.closest('.subscribe-form');

        if (!subscribeObjectMap.has(formEl)) {
            subscribeObjectMap.set(formEl, new SubscribeButton(formEl));
        }

        subscribeObjectMap.get(formEl).handleSubmit();
    }
});
