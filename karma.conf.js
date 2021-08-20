const Encore = require('@symfony/webpack-encore');

Encore.configureRuntimeEnvironment('dev');

const encoreConfig = require('./webpack-encore-config');
const webpackConfig = encoreConfig('tests/Functional/App/var/karma/build').getWebpackConfig();

delete webpackConfig.entry;
delete webpackConfig.optimization.runtimeChunk;
delete webpackConfig.optimization.splitChunks;

// Replace the mini-css-extract-plugin's loader by the style-loader
const styleExtensions = ['/\\.css$/', '/\\.s[ac]ss$/', '/\\.less$/', '/\\.styl$/'];
for (const rule of webpackConfig.module.rules) {
    if (rule.test && rule.oneOf && styleExtensions.includes(rule.test.toString())) {
        rule.oneOf.forEach((oneOf) => {
            oneOf.use[0] = 'style-loader';
        })
    }
}

// Karma options
module.exports = function(config) {
    config.set({
        frameworks: ['jasmine-ajax', 'jasmine'],
        browserConsoleLogOptions: {
            level: 'log',
            terminal: false //Remove console.* logs
        },
        files: [
            'tests/Resources/assets/js/main.js'
        ],
        preprocessors: {
            'tests/Resources/assets/js/main.js': ['webpack']
        },
        webpackMiddleware: {
            stats: 'errors-only',
            noInfo: true,
        },
        browsers: ['Firefox'],
        reporters: ['spec'],
        specReporter: {
            suppressPassed: true,
        },
        webpack: webpackConfig
    });
};
