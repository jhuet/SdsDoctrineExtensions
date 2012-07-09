<?php

namespace Hydrators;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ODM. DO NOT EDIT THIS FILE.
 */
class Sds\DoctrineExtensionsAccessControlModelPermissionHydrator implements HydratorInterface
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

        /** @Field(type="string") */
        if (isset($data['state'])) {
            $value = $data['state'];
            $return = (string) $value;
            $this->class->reflFields['state']->setValue($document, $return);
            $hydratedData['state'] = $return;
        }

        /** @Field(type="string") */
        if (isset($data['action'])) {
            $value = $data['action'];
            $return = (string) $value;
            $this->class->reflFields['action']->setValue($document, $return);
            $hydratedData['action'] = $return;
        }

        /** @Field(type="string") */
        if (isset($data['role'])) {
            $value = $data['role'];
            $return = (string) $value;
            $this->class->reflFields['role']->setValue($document, $return);
            $hydratedData['role'] = $return;
        }

        /** @Field(type="boolean") */
        if (isset($data['stateEqualToParent'])) {
            $value = $data['stateEqualToParent'];
            $return = (bool) $value;
            $this->class->reflFields['stateEqualToParent']->setValue($document, $return);
            $hydratedData['stateEqualToParent'] = $return;
        }
        return $hydratedData;
    }
}