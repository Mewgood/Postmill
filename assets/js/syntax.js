import $ from 'jquery';

const languageAliases = {
    'html': 'xml',
    'c': 'cpp',
    'js': 'javascript',
};

$('code[class^="language-"]').each(async function () {
    const nightMode = $('body').hasClass('night-mode');

    let language = this.className.replace(/.*language-(\S+).*/, '$1');

    if (languageAliases[language]) {
        language = languageAliases[language];
    }

    const theme = nightMode ? 'darkula' : 'tomorrow';

    const [{ default: hljs }, { default: definition }] = await Promise.all([
        import('highlight.js/lib/highlight'),
        import(`highlight.js/lib/languages/${language}.js`),
        import(`highlight.js/styles/${theme}.css`),
    ]);

    hljs.registerLanguage(language, definition);
    hljs.highlightBlock(this);
});
