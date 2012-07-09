<?php

namespace Hydrators;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ODM. DO NOT EDIT THIS FILE.
 */
class Sds\DoctrineExtensionsTestFreezeTestAssetDocumentStampedHydrator implements HydratorInterface
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

        /** @Field(type="boolean") */
        if (isset($data['frozen'])) {
            $value = $data['frozen'];
            $return = (bool) $value;
            $this->class->reflFields['frozen']->setValue($document, $return);
            $hydratedData['frozen'] = $return;
        }

        /** @Field(type="timestamp") */
        if (isset($data['frozenOn'])) {
            $value = $data['frozenOn'];
            $return = $value;
            $this->class->reflFields['frozenOn']->setValue($document, $return);
            $hydratedData['frozenOn'] = $return;
        }

        /** @Field(type="string") */
        if (isset($data['frozenBy'])) {
            $value = $data['frozenBy'];
            $return = (string) $value;
            $this->class->reflFields['frozenBy']->setValue($document, $return);
            $hydratedData['frozenBy'] = $return;
        }

        /** @Field(type="string") */
        if (isset($data['thawedBy'])) {
            $value = $data['thawedBy'];
            $return = (string) $value;
            $this->class->reflFields['thawedBy']->setValue($document, $return);
            $hydratedData['thawedBy'] = $return;
        }

        /** @Field(type="timestamp") */
        if (isset($data['thawedOn'])) {
            $value = $data['thawedOn'];
            $return = $value;
            $this->class->reflFields['thawedOn']->setValue($document, $return);
            $hydratedData['thawedOn'] = $return;
        }
        return $hydratedData;
    }
}