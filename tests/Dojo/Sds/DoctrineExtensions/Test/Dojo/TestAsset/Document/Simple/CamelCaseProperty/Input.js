// This code generated by Sds\DoctrineExtensions\Dojo
define([
    'dojo/_base/declare',    
    'Sds/Form/TextBox'
],
function(
    declare,    
    TextBox
){
    // Will return an input for the camelCaseProperty field

    return declare(
        'Sds/DoctrineExtensions/Test/Dojo/TestAsset/Document/Simple/CamelCaseProperty/Input',
        [            
            TextBox        
        ],
        {
            name: 'camelCaseProperty',
            
            label: 'Camel Case Property:'
        }
    );
});
