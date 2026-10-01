import Encore from '@symfony/webpack-encore';

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
const assetsDir = process.env.ASSETS_DIR ?? 'build';

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath(`public/${assetsDir}/`)
    // public path used by the web server to access the output path
    .setPublicPath(`/${assetsDir}`)
    .setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    .enableVueLoader(() => {}, { runtimeCompilerBuild: false })
    .configureMiniCssExtractPlugin(() => {}, (options) => {
        options.ignoreOrder = true;
    })
    .addLoader({
        test: /@hotwired[\\/]turbo[\\/]dist[\\/]turbo\.es2017-esm\.js$/,
        loader: 'string-replace-loader',
        options: { search: 'const PREFETCH_DELAY = 100;', replace: 'const PREFETCH_DELAY = 0;', strict: true },
    })
    .configureDefinePlugin((options) => {
        options.__VUE_I18N_FULL_INSTALL__ = JSON.stringify(true);
        options.__VUE_I18N_LEGACY_API__ = JSON.stringify(false);
        options.__INTLIFY_PROD_DEVTOOLS__ = JSON.stringify(false);
        options.__INTLIFY_DROP_MESSAGE_COMPILER__ = JSON.stringify(false);
    })

    // enables the Symfony UX Stimulus bridge (used in assets/stimulus_bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()

    // Displays build status system notifications to the user
    // .enableBuildNotifications()

    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // Configure JS and CSS minimizers
    // .configureJsMinimizerPlugin((options, MinimizerPlugin) => {
    //     options.minify = MinimizerPlugin.esbuildMinify
    // })
    // .configureCssMinimizerPlugin((options, MinimizerPlugin) => {
    //     options.minify = MinimizerPlugin.lightningCssMinify;
    // })

    // configure Babel
    .configureBabel((config) => {
        config.plugins.push(['polyfill-corejs3', { method: 'usage-global', version: '3.49' }]);
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()
;

export default await Encore.getWebpackConfig();
