// This code generated by Sds\DoctrineExtensions\Dojo
define([
    'dojo/_base/declare',    
    'Sds/Validator/Group',
    'Sds/Test/ClassValidator1',
    'Sds/Test/ClassValidator2'
],
function(
    declare,    
    Group,
    ClassValidator1,
    ClassValidator2
){
    // Will return a multi field validator

    return declare(
        'Sds/DoctrineExtensions/Test/Dojo/TestAsset/Document/Simple/MultiFieldValidator',
        [            
            Group        
        ],
        {
            validators: [
            	"new ClassValidator1",
            	"new ClassValidator2({\n    \u0022option1\u0022: \u0022a\u0022,\n    \u0022option2\u0022: \u0022b\u0022\n})"
            ]
        }
    );
});
