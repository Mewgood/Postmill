'use strict';

const Encore = require('@symfony/webpack-encore');
const fs = require('fs');
const merge = require('webpack-merge');

Encore
    .addEntry('main', './assets/js/main.js')
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
    fs.readdirSync(directory, { withFileTypes: true }).forEach(function (file) {
        if (file.name[0] !== '_') {
            const filePath = directory + '/' + file.name;

            if (file.isFile() && /\.(le|c)ss$/i.test(file.name)) {
                const entryName = prefix + file.name.replace(/\..+?$/, '');

                Encore.addStyleEntry(entryName, filePath);
            } else if (file.isDirectory()) {
                const newPrefix = prefix + file.name + '/';

                addStyleEntrypoints(filePath, newPrefix);
            }
        }
    });
})(__dirname + '/assets/css', '');

module.exports = merge(Encore.getWebpackConfig(), {
    externals: {
        "fosjsrouting": "Routing",
    },
});
