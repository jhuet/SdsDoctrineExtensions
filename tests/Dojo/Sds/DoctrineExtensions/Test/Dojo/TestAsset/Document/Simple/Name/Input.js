// This code generated by Sds\DoctrineExtensions\Dojo
define([
    'dojo/_base/declare',    
    'Sds/Form/ValidationTextBox',
    'Sds/DoctrineExtensions/Test/Dojo/TestAsset/Document/Simple/Name/Validator'
],
function(
    declare,    
    ValidationTextBox,
    NameValidator
){
    // Will return an input for the name field

    return declare(
        'Sds/DoctrineExtensions/Test/Dojo/TestAsset/Document/Simple/Name/Input',
        [            
            ValidationTextBox        
        ],
        {
            validator: new NameValidator,
            
            name: 'name',
            
            label: 'Name:'
        }
    );
});
