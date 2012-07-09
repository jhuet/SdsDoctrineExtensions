<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Freeze\Mapping\MetadataInjector;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Sds\DoctrineExtensions\Freeze\Mapping\Annotation\FreezeField as SDS_FreezeField;
use Sds\DoctrineExtensions\AbstractMetadataInjector;

/**
 * Adds freeze values to classmetadata
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Freeze extends AbstractMetadataInjector
{
    /**
     * Freeze
     */
    const freezeField = 'freezeField';

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass(ClassMetadataInfo $class)
    {
        $reflClass = $class->getReflectionClass();

        if (!$reflClass->implementsInterface('Sds\Common\Freeze\FreezeableInterface')){
            return;
        }
        
        //Property annotations
        foreach ($reflClass->getProperties() as $property) {
            if ($class->isMappedSuperclass && !$property->isPrivate() || $class->isInheritedField($property->name)) {
                continue;
            }

            foreach ($this->reader->getPropertyAnnotations($property) as $annotation) {
                if ($annotation instanceof SDS_FreezeField) {
                    $class->freezeField = $property->name;
                    return;
                }
            }
        }
    }
}