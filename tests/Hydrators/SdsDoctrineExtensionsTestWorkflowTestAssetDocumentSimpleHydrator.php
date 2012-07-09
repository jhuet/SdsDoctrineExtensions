<?php

namespace Hydrators;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ODM. DO NOT EDIT THIS FILE.
 */
class Sds\DoctrineExtensionsTestWorkflowTestAssetDocumentSimpleHydrator implements HydratorInterface
{
    private $dm;
    private $unitOfWork;
    private $class;

    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $class)
    {
        $this->dm = $dm;
        $this->unitOfWork = $uow;
        $this->class = $class;
    }

    public function hydrate($document, $data, array $hints = array())
    {
        $hydratedData = array();

        /** @Field(type="custom_id") */
        if (isset($data['_id'])) {
            $value = $data['_id'];
            $return = $value;
            $this->class->reflFields['id']->setValue($document, $return);
            $hydratedData['id'] = $return;
        }

        /** @Field(type="string") */
        if (isset($data['name'])) {
            $value = $data['name'];
            $return = (string) $value;
            $this->class->reflFields['name']->setValue($document, $return);
            $hydratedData['name'] = $return;
        }

        /** @EmbedOne */
        if (isset($data['workflow'])) {
            $embeddedDocument = $data['workflow'];
            $className = $this->dm->getClassNameFromDiscriminatorValue($this->class->fieldMappings['workflow'], $embeddedDocument);
            $embeddedMetadata = $this->dm->getClassMetadata($className);
            $return = $embeddedMetadata->newInstance();

            $embeddedData = $this->dm->getHydratorFactory()->hydrate($return, $embeddedDocument, $hints);
            $this->unitOfWork->registerManaged($return, null, $embeddedData);
            $this->unitOfWork->setParentAssociation($return, $this->class->fieldMappings['workflow'], $document, 'workflow');

            $this->class->reflFields['workflow']->setValue($document, $return);
            $hydratedData['workflow'] = $return;
        }

        /** @Field(type="string") */
        if (isset($data['state'])) {
            $value = $data['state'];
            $return = (string) $value;
            $this->class->reflFields['state']->setValue($document, $return);
            $hydratedData['state'] = $return;
        }
        return $hydratedData;
    }
}