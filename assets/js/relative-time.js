import { distanceInWords, distanceInWordsToNow, isBefore } from 'date-fns';
import translator from 'bazinga-translator';

/**
 * @param {ParentNode} el
 */
export function makeTimesRelative(el) {
    loadDateFnsLocale().then(locale => {
        el.querySelectorAll('.js-relative-time').forEach(el => {
            const relativeTime = distanceInWordsToNow(el.dateTime, {
                addSuffix: true,
                locale,
            });

            el.innerText = translator.trans('time.at_relative_time', {
                relative_time: relativeTime,
            });
        });

        el.querySelectorAll('.js-relative-time-diff').forEach(el => {
            const timeA = el.dateTime;
            const timeB = el.getAttribute('data-compare-to');

            const relativeTime = distanceInWords(timeA, timeB, { locale });

            const format = isBefore(timeB, timeA)
                ? 'time.later_format'
                : 'time.earlier_format';

            el.innerText = translator.trans(format, {
                relative_time: relativeTime,
            });
        });
    });
}

/**
 * @param {string} lang
 *
 * @returns {Promise<null|object>}
 */
function loadDateFnsLocale(lang = document.documentElement.lang || 'en') {
    if (lang === 'en') {
        return Promise.resolve(null);
    }

    return import(`date-fns/locale/${lang}/index.js`)
        .then(locale => locale)
        .catch(() => {
            const i = lang.indexOf('-');

            if (i !== -1) {
                const newLang = lang.substring(0, i);

                console.info(`Couldn't load ${lang}; trying ${newLang}`);

                return loadDateFnsLocale(newLang);
            }

            throw new Error(`Couldn't load ${lang}`);
        });
}
