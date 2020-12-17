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
// Plaisio\Console\Helper\TypeScript\TypeScriptMarkHelper::updated
