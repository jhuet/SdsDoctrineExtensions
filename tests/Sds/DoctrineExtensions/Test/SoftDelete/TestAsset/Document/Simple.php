<?php

namespace Sds\DoctrineExtensions\Test\SoftDelete\TestAsset\Document;

use Sds\Common\SoftDelete\SoftDeleteableInterface;
use Sds\DoctrineExtensions\SoftDelete\DataModel\SoftDeleteableTrait;

//Annotation imports
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Sds\DoctrineExtensions\Annotation\Annotations as Sds;

/** @ODM\Document */
class Simple implements SoftDeleteableInterface {

    use SoftDeleteableTrait;

    /**
     * @ODM\Id(strategy="UUID")
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     */
    protected $name;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }
}
