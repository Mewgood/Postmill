const languageAliases = {
    'html': 'xml',
    'c': 'cpp',
    'js': 'javascript',
};

function highlight(el) {
    let language = el.className.replace(/.*language-(\S+).*/, '$1');

    if (languageAliases[language]) {
        language = languageAliases[language];
    }

    Promise.all([
        import('highlight.js/lib/highlight'),
        import(`highlight.js/lib/languages/${language}.js`),
    ]).then(imports => {
        const [{ default: hljs }, { default: definition }] = imports;

        hljs.registerLanguage(language, definition);
        hljs.highlightBlock(el);
    });
}

/**
 * @param {ParentNode} root
 */
export function highlightRoot(root) {
    root.querySelectorAll('code[class^="language-"]').forEach(el => highlight(el));
}
