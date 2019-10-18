import router from 'fosjsrouting';
import translator from 'bazinga-translator';
import { fetch, ok } from './lib/http';

const VOTE_UP = 1;
const VOTE_NONE = 0;
const VOTE_DOWN = -1;

const STATUS_NONE = 0;
const STATUS_LOADING = 1;
const STATUS_FAILED = 2;

class Vote {
    constructor(voteFormEl) {
        this.el = voteFormEl;
        this.status = STATUS_NONE;
        this.netScore = Number(this.el.getAttribute('data-score'));

        if (this.el.classList.contains('vote--user-upvoted')) {
            this.userChoice = VOTE_UP;
        } else if (this.el.classList.contains('vote--user-downvoted')) {
            this.userChoice = VOTE_DOWN;
        } else {
            this.userChoice = VOTE_NONE;
        }
    }

    get url() {
        return router.generate(this.el.getAttribute('data-route'), {
            id: this.el.getAttribute('data-id'),
            _format: 'json',
        });
    }

    vote(choice) {
        choice = Number(choice);

        if (this.status === STATUS_LOADING) {
            return;
        }

        this.status = STATUS_LOADING;
        this.userChoice = choice;
        this.updateView();

        const data = new FormData(this.el);
        data.append('choice', choice);

        fetch(this.url, {
            method: 'POST',
            body: data,
        })
            .then(response => ok(response))
            .then(response => response.json())
            .then(response => {
                this.status = STATUS_NONE;
                this.netScore = response.netScore;
            })
            .catch(e => {
                this.status = STATUS_FAILED;

                throw e;
            })
            .finally(() => this.updateView());
    }

    updateView() {
        const voteUpEl = this.el.querySelector('.vote__up');
        const voteDownEl = this.el.querySelector('.vote__down');
        const netScoreEl = this.el.querySelector('.vote__net-score');

        this.el.setAttribute('data-score', this.netScore);
        this.el.classList.remove(
            'vote--user-upvoted',
            'vote--user-downvoted',
            'vote--failed'
        );

        if (this.status === STATUS_FAILED) {
            this.el.classList.add('vote--failed');
        }

        if (this.userChoice === VOTE_UP) {
            this.el.classList.add('vote--user-upvoted');
        } else if (this.userChoice === VOTE_DOWN) {
            this.el.classList.add('vote--user-downvoted');
        }

        if (this.userChoice === VOTE_UP) {
            voteUpEl.title = translator.trans('action.retract_upvote');
            voteUpEl.value = VOTE_NONE;
        } else {
            voteUpEl.title = translator.trans('action.upvote');
            voteUpEl.value = VOTE_UP;
        }

        if (this.userChoice === VOTE_DOWN) {
            voteDownEl.title = translator.trans('action.retract_downvote');
            voteDownEl.value = VOTE_NONE;
        } else {
            voteDownEl.title = translator.trans('action.downvote');
            voteDownEl.value = VOTE_DOWN;
        }

        if (this.status === STATUS_LOADING) {
            netScoreEl.innerHTML = this.el.getAttribute('data-load-prototype');
        } else if (this.netScore < 0) {
            netScoreEl.innerHTML = '&minus;' + Math.abs(this.netScore);
        } else {
            netScoreEl.innerText = this.netScore;
        }
    }
}

const voteObjectMap = new WeakMap();

addEventListener('submit', event => {
    if (event.target.closest('.user-logged-in .vote')) {
        event.preventDefault();
    }
});

addEventListener('click', event => {
    const el = event.target.closest('.user-logged-in .vote__button');

    if (el) {
        const formEl = el.closest('.vote');

        if (!voteObjectMap.has(formEl)) {
            voteObjectMap.set(el, new Vote(formEl));
        }

        voteObjectMap.get(el).vote(el.value);
    }
});
