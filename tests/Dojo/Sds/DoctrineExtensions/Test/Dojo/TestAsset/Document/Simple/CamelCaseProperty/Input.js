// This code generated by Sds\DoctrineExtensions\Dojo
define([
    'dojo/_base/declare',
    'Sds/Common/Form/TextBox'
],
function(
    declare,
    TextBox
){
    // Will return an Input for the camelCaseProperty field

    return declare(
        'Sds/DoctrineExtensions/Test/Dojo/TestAsset/Document/Simple/CamelCaseProperty/Input',
        [TextBox],
        {
            name: "camelCaseProperty",

            label: "Camel Case Property:"
        }
    );
});
