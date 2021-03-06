<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Serializer;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Sds\DoctrineExtensions\Accessor\Accessor;
use Sds\DoctrineExtensions\Exception;

/**
 * Provides static methods for serializing documents
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Serializer {

    const IGNORE_WHEN_UNSERIALIZING = 'ignore_when_unserializing';
    const IGNORE_WHEN_SERIALIZING = 'ignore_when_serializing';
    const IGNORE_ALWAYS = 'ignore_always';
    const IGNORE_NEVER = 'ignore_never';

    /** @var array */
    protected static $typeSerializers = [];

    /** @var int */
    protected static $maxNestingDepth = 1;

    /** @var int */
    protected static $nestingDepth = 0;

    /**
     * @param string $type
     * @param string $serializer
     */
    public static function addTypeSerializer($type, $serializer){
        self::$typeSerializers[(string) $type] = (string) $serializer;
    }

    /**
     * @param string $type
     */
    public static function removeTypeSerializer($type){
        unset(self::$typeSerializers[(string) $type]);
    }

    /**
     * @param int $maxNestingDepth
     */
    public static function setMaxNestingDepth($maxNestingDepth){
        self::$maxNestingDepth = (int) $maxNestingDepth;
    }

    /**
     *
     * @param object $document
     * @param DocumentManager $documentManager
     * @return array
     */
    public static function toArray($document, DocumentManager $documentManager){
        return self::serialize($document, $documentManager);
    }

    /**
     *
     * @param object $document
     * @param DocumentManager $documentManager
     * @return string
     */
    public static function toJson($document, DocumentManager $documentManager){
        return json_encode(self::serialize($document, $documentManager));
    }

    /**
     * Will take an associative array representing a document, and apply the
     * serialization metadata rules to that array.
     *
     * @param array $array
     * @param string $className
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @return array
     */
    public static function applySerializeMetadataToArray(array $array, $className, DocumentManager $documentManager) {

        $classMetadata = $documentManager->getClassMetadata($className);
        $fieldList = self::fieldListForSerialize($classMetadata);
        $return = array_merge($array, self::serializeClassNameAndDiscriminator($classMetadata));

        foreach ($classMetadata->fieldMappings as $field=>$mapping){

            if ( ! in_array($field, $fieldList)){
                if (isset($return[$field])){
                    unset($return[$field]);
                }
                continue;
            }

            if ( isset($mapping['id']) && $mapping['id'] && isset($array['_id'])){
                $return[$field] = $array['_id'];
                unset($return['_id']);
            }

            if ( ! isset($return[$field])){
                continue;
            }

            switch (true){
                case isset($mapping['embedded']) && $mapping['type'] == 'one':
                    $return[$field] = self::applySerializeMetadataToArray(
                        $return[$field],
                        $mapping['targetDocument'],
                        $documentManager
                    );
                    break;
                case isset($mapping['embedded']) && $mapping['type'] == 'many':
                    foreach($return[$field] as $index => $embedArray){
                        $return[$field][$index] = self::applySerializeMetadataToArray(
                            $embedArray,
                            $mapping['targetDocument'],
                            $documentManager
                        );
                    }
                    break;
                case isset($mapping['reference']) && $mapping['type'] == 'one':
                    if (self::$nestingDepth < self::$maxNestingDepth) {
                        self::$nestingDepth++;
                        $referenceSerializer = self::getReferenceSerializer($field, $classMetadata);
                        $return[$field] = $referenceSerializer::serialize(
                            is_array($return[$field]) ? $return[$field]['$id'] : $return[$field],
                            $mapping,
                            $documentManager
                        );
                        self::$nestingDepth--;
                    }
                    break;
                case isset($mapping['reference']) && $mapping['type'] == 'many':
                    if (self::$nestingDepth < self::$maxNestingDepth) {
                        self::$nestingDepth++;
                        $referenceSerializer = self::getReferenceSerializer($field, $classMetadata);
                        foreach($return[$field] as $index => $referenceDocument){
                            $return[$field][$index] = $referenceSerializer::serialize(
                                is_array($referenceDocument) ? $referenceDocument['$id'] : $referenceDocument,
                                $mapping,
                                $documentManager
                            );
                        }
                        self::$nestingDepth--;
                    }
                    break;
                case array_key_exists($mapping['type'], self::$typeSerializers):
                    $typeSerializer = self::$typeSerializers[$mapping['type']];
                    $return[$field] = $typeSerializer::serialize($return[$field]);
                    break;
            }
        }

        return $return;
    }

    protected static function serializeClassNameAndDiscriminator(ClassMetadata $classMetadata) {

        $return = array();

        if (isset($classMetadata->serializer['className']) &&
            $classMetadata->serializer['className']
        ) {
            $return[$classMetadata->serializer['classNameProperty']] = $classMetadata->name;
        }

        if (isset($classMetadata->serializer['discriminator']) &&
            $classMetadata->serializer['discriminator'] &&
            $classMetadata->hasDiscriminator()
        ) {
            $return[$classMetadata->discriminatorField['name']] = $classMetadata->discriminatorValue;
        }

        return $return;
    }

    public static function fieldListForUnserialize(ClassMetadata $classMetadata){

        $return = [];

        foreach ($classMetadata->fieldMappings as $field=>$mapping){
            if (isset($classMetadata->serializer['fields'][$field]['ignore']) &&
                (
                    $classMetadata->serializer['fields'][$field]['ignore'] == self::IGNORE_WHEN_UNSERIALIZING ||
                    $classMetadata->serializer['fields'][$field]['ignore'] == self::IGNORE_ALWAYS
                )
            ){
               continue;
            }
            $return[] = $field;
        }

        return $return;
    }

    public static function fieldListForSerialize(ClassMetadata $classMetadata){

        $return = [];

        foreach ($classMetadata->fieldMappings as $field=>$mapping){
            if (isset($classMetadata->serializer['fields'][$field]['ignore']) &&
                (
                    $classMetadata->serializer['fields'][$field]['ignore'] == self::IGNORE_WHEN_SERIALIZING ||
                    $classMetadata->serializer['fields'][$field]['ignore'] == self::IGNORE_ALWAYS
                )
            ){
               continue;
            }
            $return[] = $field;
        }

        return $return;
    }

    /**
     *
     * @param object | array $document
     * @param DocumentManager $documentManager
     * @return array
     * @throws \BadMethodCallException
     */
    protected static function serialize($document, DocumentManager $documentManager){

        $classMetadata = $documentManager->getClassMetadata(get_class($document));
        $fieldList = self::fieldListForSerialize($classMetadata);
        $return = self::serializeClassNameAndDiscriminator($classMetadata);

        foreach ($classMetadata->fieldMappings as $field=>$mapping){

            if ( ! in_array($field, $fieldList)){
                continue;
            }

            $getMethod = Accessor::getGetter($classMetadata, $field, $document);

            switch (true){
                case isset($mapping['embedded']) && $mapping['type'] == 'one':
                    if ($embedDocument = $document->$getMethod()) {
                        $return[$field] = self::serialize($embedDocument, $documentManager);
                    }
                    break;
                case isset($mapping['embedded']) && $mapping['type'] == 'many':
                    if ($embedDocuments = $document->$getMethod()) {
                        $return[$field] = array();
                        foreach($embedDocuments as $embedDocument){
                            $return[$field][] = self::serialize($embedDocument, $documentManager);
                        }
                    }
                    break;
                case isset($mapping['reference']) && $mapping['type'] == 'one':
                    if (self::$nestingDepth < self::$maxNestingDepth) {
                        self::$nestingDepth++;
                        if ($referencedDocument = $document->$getMethod()) {
                            $referenceSerializer = self::getReferenceSerializer($field, $classMetadata);
                            $serializedDocument = $referenceSerializer::serialize(
                                Accessor::getId($documentManager->getClassMetadata($mapping['targetDocument']), $referencedDocument),
                                $mapping,
                                $documentManager
                            );
                            if ($serializedDocument){
                                $return[$field] = $serializedDocument;
                            }
                        }
                        self::$nestingDepth--;
                    }
                    break;
                case isset($mapping['reference']) && $mapping['type'] == 'many':
                    if (self::$nestingDepth < self::$maxNestingDepth) {
                        self::$nestingDepth++;
                        if ($referencedDocuments = $document->$getMethod()) {
                            $referenceSerializer = self::getReferenceSerializer($field, $classMetadata);
                            foreach($referencedDocuments->getMongoData() as $referenceDocument){
                                $serializedDocument = $referenceSerializer::serialize(
                                    is_array($referenceDocument) ? $referenceDocument['$id'] : (string) $referenceDocument,
                                    $mapping,
                                    $documentManager
                                );
                                if ($serializedDocument){
                                    $return[$field][] = $serializedDocument;
                                }
                            }
                        }
                        self::$nestingDepth--;
                    }
                    break;
                case array_key_exists($mapping['type'], self::$typeSerializers):
                    $typeSerializer = self::$typeSerializers[$mapping['type']];
                    $return[$field] = $typeSerializer::serialize($document->$getMethod());
                    break;
                default:
                    $return[$field] = $document->$getMethod();
            }
        }
        return $return;
    }

    protected static function getReferenceSerializer($field, $classMetadata){
        if (isset($classMetadata->serializer['fields'][$field]['referenceSerializer'])){
            return $classMetadata->serializer['fields'][$field]['referenceSerializer'];
        } else {
            return 'Sds\DoctrineExtensions\Serializer\Reference\RefLazy';
        }
    }

    /**
     * This will create a document from the supplied array.
     * WARNING: the constructor of the document will not be called.
     *
     * @param array $data
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @param string $classNameKey
     * @param string $className
     * @return object
     */
    public static function fromArray(
        array $data,
        DocumentManager $documentManager,
        $classNameKey = '_className',
        $className = null
    ) {
        return self::unserialize($data, $documentManager, $classNameKey, $className);
    }

    /**
     * This will create a document from the supplied json string.
     * WARNING: the constructor of the document will not be called.
     *
     * @param string $data
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @param string $classNameKey
     * @param string $className
     * @return object
     */
    public static function fromJson(
        $data,
        DocumentManager $documentManager,
        $classNameKey = '_className',
        $className = null
    ) {
        return self::unserialize(json_dencode($data), $documentManager, $classNameKey, $className);
    }

    /**
     *
     * @param array $data
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @param string $classNameKey
     * @param string $className
     * @return \Sds\DoctrineExtensions\Serializer\className
     * @throws \Exception
     * @throws \BadMethodCallException
     */
    protected static function unserialize(
        array $data,
        DocumentManager $documentManager,
        $classNameKey = '_className',
        $className = null
    ) {

        if (! isset($className) &&
            ! isset($data[$classNameKey])
        ) {
            throw new Exception\InvalidArgumentException(sprintf('Both className and classNameKey %s are not set', $classNameKey));
        }

        $className = isset($data[$classNameKey]) ? $data[$classNameKey] : $className;

        if (! class_exists($className)){
            throw new Exception\ClassNotFoundException(sprintf('ClassName %s could not be loaded', $className));
        }

        $metadata = $documentManager->getClassMetadata($className);

        // Attempt to load prexisting document from db
        if (isset($data[$metadata->identifier])){
            $document = $documentManager->getRepository($className)->find($data[$metadata->identifier]);
        }
        if (isset($document)){
            $loadedFromDocumentManager = true;
        } else {
            $loadedFromDocumentManager = false;
            $reflection = new \ReflectionClass($className);
            $document = $reflection->newInstanceWithoutConstructor();
        }

        foreach ($metadata->fieldMappings as $field=>$mapping){

            if (!isset($data[$field])) {
                continue;
            }
            if ($field == $metadata->identifier && $loadedFromDocumentManager){
                continue;
            }

            $setMethod = Accessor::getSetter($metadata, $field, $document);

            switch (true){
                case isset($mapping['embedded']) && $mapping['type'] == 'one':
                    $document->$setMethod(self::unserialize(
                        $data[$field],
                        $documentManager,
                        null,
                        $mapping['targetDocument']
                    ));
                    break;
                case isset($mapping['embedded']) && $mapping['type'] == 'many':
                    $collection = array();
                    foreach($data[$field] as $embedData){
                        $collection[] = self::unserialize(
                            $embedData,
                            $documentManager,
                            null,
                            $mapping['targetDocument']
                        );
                    }
                    $document->$setMethod($collection);
                    break;
                case isset($mapping['reference']) && $mapping['type'] == 'one':
                    if (isset($data[$field]['$ref'])){
                        $pieces = explode('/', $data[$field]['$ref']);
                        $id = $pieces[count($pieces) - 1];
                        $document->$setMethod($documentManager->getReference($mapping['targetDocument'], $id));
                    } else {
                        $document->$setMethod(self::unserialize(
                            $data[$field],
                            $documentManager,
                            null,
                            $mapping['targetDocument']
                        ));
                    }
                    break;
                case isset($mapping['reference']) && $mapping['type'] == 'many':
                    $newArray = [];
                    foreach($data[$field] as $value){
                        if (isset($value['$ref'])){
                            $pieces = explode('/', $value['$ref']);
                            $id = $pieces[count($pieces) - 1];
                            $newArray[] = $documentManager->getReference($mapping['targetDocument'], $id);
                        } else {
                            $newArray[] = self::unserialize(
                                $value,
                                $documentManager,
                                null,
                                $mapping['targetDocument']
                            );
                        }
                    }
                    $document->$setMethod($newArray);
                    break;
                case array_key_exists($mapping['type'], self::$typeSerializers):
                    $typeSerializer = self::$typeSerializers[$mapping['type']];
                    $document->$setMethod($typeSerializer::unserialize($data[$field]));
                    break;
                default:
                    $document->$setMethod($data[$field]);
            }
        }

        return $document;
    }
}
