module.exports = function (outputPath) {
    var Encore = require('@symfony/webpack-encore');

    if (!Encore.isRuntimeEnvironmentConfigured()) {
        Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
    }

    Encore
        .setOutputPath(outputPath)
        .setPublicPath('/build')
        .addEntry('app', './tests/Functional/App/assets/js/app.js')
        .splitEntryChunks()
        .enableSingleRuntimeChunk()
        .cleanupOutputBeforeBuild()
        .enableBuildNotifications()
        .enableSourceMaps(!Encore.isProduction())
        .enableVersioning(false)
        .configureBabelPresetEnv((config) => {
            config.useBuiltIns = 'usage';
            config.corejs = 3;
        })
    ;

    return Encore;
}
