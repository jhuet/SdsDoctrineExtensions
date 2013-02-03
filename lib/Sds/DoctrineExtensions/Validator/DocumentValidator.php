<?php
/**
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Validator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Sds\Validator\Factory as ValidatorFactory;
use Sds\Validator\ValidatorResult;
use Sds\DoctrineExtensions\Accessor\Accessor;
use Sds\DoctrineExtensions\Annotation\Annotations as Sds;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DocumentValidator implements DocumentValidatorInterface
{

    // TODO: make use of a validatorCache - the validators are re-instatated every validation at the moment.
    protected $validatorCache = [];

    //Only needs to be set when validating documents that have encrypted fields.
    protected $documentManager;

    public function setDocumentManager($documentManager) {
        $this->documentManager = $documentManager;
    }

    /**
     * Using zend\form\annotation\validator annotations, this method will check if a document
     * is valid.
     *
     * @param object $document
     * @return boolean
     */
    public function isValid($document, ClassMetadata $metadata) {
        $messages = [];
        $result = true;

        if ( ! isset($metadata->validator)){
            return new ValidatorResult(true, []);
        }

        // Property level validators
        if (isset($metadata->validator['fields'])){
            foreach ($metadata->validator['fields'] as $field => $validatorDefinition){

                $getter = Accessor::getGetter($metadata, $field, $document);

                //check for hashed or encrypted values - if the field has been persisted, and is unchanged,
                //it is assumed to be vaild. If the encrypted value is passed to the validators, then
                //it is likely fail, which isn't correct.
                if ((isset($metadata->crypt['hash'][$field]) ||
                    isset($metadata->crypt['blockCipher'][$field])) &&
                    isset($this->documentManager)
                ) {
                    $originalDocumentData = $this->documentManager->getUnitOfWork()->getOriginalDocumentData($document);
                    if (isset($originalDocumentData) &&
                        isset($originalDocumentData[$field]) &&
                        $originalDocumentData[$field] == $document->$getter()
                    ){
                        //encrypted value hasn't changed, so skip validation of it.
                        continue;
                    }
                }

                $validator = ValidatorFactory::create($validatorDefinition);
                $value = $document->$getter();

                $validatorResult = $validator->isValid($value);
                if ( ! $validatorResult->getResult()){
                    foreach ($validatorResult->getMessages() as $message){
                        $messages[] = sprintf('Field %s: %s', $field, $message);
                    }
                    $result = false;
                }
            }
        }

        // Class level validators
        if (isset($metadata->validator['document'])){
            $validator = ValidatorFactory::create($metadata->validator['document']);
            $validatorResult = $validator->isValid($value);
            if ( ! $validatorResult->getResult()){
                $messages = array_merge($messages, $validatorResult->getMessages());
                $result = false;
            }
        }

        return new ValidatorResult($result, $messages);
    }
}
