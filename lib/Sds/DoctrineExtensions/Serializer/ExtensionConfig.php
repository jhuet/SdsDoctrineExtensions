<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Serializer;

use Sds\DoctrineExtensions\AbstractExtensionConfig;
use Sds\DoctrineExtensions\ClassNamePropertyTrait;
/**
 * Defines the resouces this extension requires
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class ExtensionConfig extends AbstractExtensionConfig {

    use ClassNamePropertyTrait;

    /** @var array */
    protected $typeSerializers = [];

    /** @var int */
    protected $maxNestingDepth = 1;

    /** @var array */
    protected $dependencies = [
        'Sds\DoctrineExtensions\Annotation' => true,
        'Sds\DoctrineExtensions\Accessor' => true
    ];

    /**
     * @return array
     */
    public function getTypeSerializers() {
        return $this->typeSerializers;
    }

    /**
     * @param array $typeSerializers
     */
    public function setTypeSerializers($typeSerializers) {
        $this->typeSerializers = $typeSerializers;
    }

    /**
     * @return int
     */
    public function getMaxNestingDepth() {
        return $this->maxNestingDepth;
    }

    /**
     * @param type $maxNestingDepth
     */
    public function setMaxNestingDepth($maxNestingDepth) {
        $this->maxNestingDepth = (int) $maxNestingDepth;
    }
}
