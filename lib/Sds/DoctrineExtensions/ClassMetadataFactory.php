<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory as DoctrineClassMetadataFactory;

/**
 * Extends ClassMetadataFactory to support Sds metadata
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class ClassMetadataFactory extends DoctrineClassMetadataFactory
{

    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string $className
     * @return ClassMetadata
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }
}
