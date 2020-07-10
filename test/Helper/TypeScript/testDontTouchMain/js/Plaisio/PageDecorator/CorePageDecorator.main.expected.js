//----------------------------------------------------------------------------------------------------------------------
// @ts-ignore
requirejs.config({
    baseUrl: '/js',
    paths: {
        'jquery': 'jquery/jquery',
        'jquery.cookie': 'js-cookie/js.cookie',
        'js-cookie': 'js-cookie/js.cookie'
    }
});
//----------------------------------------------------------------------------------------------------------------------
// @ts-ignore
require(["Plaisio/PageDecorator/CorePageDecorator"]);
// Modified by Plaisio\Console\Helper\TypeScript\TypeScriptFixHelper
