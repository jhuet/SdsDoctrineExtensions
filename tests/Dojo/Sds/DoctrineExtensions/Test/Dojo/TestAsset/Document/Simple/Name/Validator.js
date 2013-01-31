// This code generated by Sds\DoctrineExtensions\Dojo
define([
    'dojo/_base/declare',    
    'Sds/Validator/Group',
    'Sds/Validator/Required',
    'Sds/Test/NameValidator1',
    'Sds/Test/NameValidator2'
],
function(
    declare,    
    Group,
    Required,
    NameValidator1,
    NameValidator2
){
    // Will return a validator that can be used to check
    // the name field

    return declare(
        'Sds/DoctrineExtensions/Test/Dojo/TestAsset/Document/Simple/Name/Validator',
        [            
            Group        
        ],
        {
            field: 'name',
            
            validators: [
            	new Required,
            	new NameValidator1,
            	new NameValidator2({
            		"option1":"b",
            		"option2":"b"
            	}	)
            ]
        }
    );
});
