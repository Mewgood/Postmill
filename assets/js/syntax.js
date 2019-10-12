const languageAliases = {
    'html': 'xml',
    'c': 'cpp',
    'js': 'javascript',
};

document.querySelectorAll('code[class^="language-"]').forEach(async el => {
    let language = el.className.replace(/.*language-(\S+).*/, '$1');

    if (languageAliases[language]) {
        language = languageAliases[language];
    }

    const [
        { default: hljs },
        { default: definition },
    ] = await Promise.all([
        import('highlight.js/lib/highlight'),
        import(`highlight.js/lib/languages/${language}.js`),
    ]);

    hljs.registerLanguage(language, definition);
    hljs.highlightBlock(el);
});
