'use strict';

const Encore = require('@symfony/webpack-encore');
const fs = require('fs');

Encore
    .addEntry('main', './assets/js/main.js')
    .addExternals({
        'bazinga-translator': 'Translator',
        'fosjsrouting': 'Routing',
    })
    .cleanupOutputBeforeBuild()
    .copyFiles({
        from: './assets/icons',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.svg$/i,
    })
    .enableLessLoader()
    .enableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()
    .enableIntegrityHashes()
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .createSharedEntry('vendor', './assets/js/vendor.js');

(function addStyleEntrypoints(directory, prefix) {
    fs.readdirSync(directory, { withFileTypes: true })
        .filter(file => !file.name.startsWith('_'))
        .forEach(file => {
            const filePath = `${directory}/${file.name}`;

            if (file.isFile() && prefix && /^index\.(le|c)ss$/i.test(file.name)) {
                Encore.addStyleEntry(prefix.replace(/\/$/, ''), filePath);
            } else if (file.isFile() && /\.(le|c)ss$/i.test(file.name)) {
                const entryName = file.name.replace(/\..+?$/, '');

                Encore.addStyleEntry(prefix + entryName, filePath);
            } else if (file.isDirectory()) {
                const newPrefix = prefix + file.name + '/';

                addStyleEntrypoints(filePath, newPrefix);
            }
        });
})(__dirname + '/assets/css', '');

module.exports = Encore.getWebpackConfig();
