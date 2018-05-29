const mix = require('laravel-mix')
const path = require('path')
const webpack = require('webpack')

function resolve (dir) {
    return path.join(__dirname, dir)
}

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.webpackConfig({
    module: {
        rules: [
            {
                test: /\.svg$/,
                loader: 'svg-sprite-loader',
                include: [
                    resolve('resources/admin/icons'),
                    resolve('resources/merchant/icons')
                ],
                options: {
                    symbolId: 'icon-[name]'
                }
            },
            {
                test: /\.(png|jpe?g|gif|svg)(\?.*)?$/,
                loader: 'url-loader',
                exclude: [
                    resolve('resources/admin/icons'),
                    resolve('resources/merchant/icons')
                ],
                options: {
                    limit: 10000,
                    name: 'images/[name].[ext]?[hash]'
                }
            },
            {
                enforce: 'pre',
                exclude: /node_modules/,
                loader: 'eslint-loader',
                test: /\.(js|vue|json)?$/
            },
            {
                test: /\.sass$/,
                loaders: ['style', 'css', 'sass']
            }
        ]
    },
    resolve: {
        extensions: ['.js', '.vue', '.json'],
        alias: {
            '@': resolve('resources')
        }
    },
    output: {
        publicPath: '/',
        filename: '[name].js?id=[chunkhash:20]',
        chunkFilename: 'dist/[name].chunk.js?id=[chunkhash:20]'
    }
})

// 资源打包
mix.js('resources/admin/index.js', 'dist/admin.js').js('resources/merchant/index.js', 'dist/merchant.js')
    .extract(['vue', 'axios', 'vue-count-to', 'vue-i18n', 'vue-router', 'vuex', 'xlsx', 'element-ui'])

// 添加版本号
mix.version()
