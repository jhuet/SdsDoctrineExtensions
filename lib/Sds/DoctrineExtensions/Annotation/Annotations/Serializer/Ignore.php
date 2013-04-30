<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Annotation\Annotations\Serializer;

use Doctrine\Common\Annotations\Annotation;
use Sds\DoctrineExtensions\Serializer\Serializer as Constants;

/**
 * Mark a field to be skipped during serialization. Must be used in the context
 * of the Serializer annotation
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 *
 * @Annotation
 */
final class Ignore extends Annotation
{
    const event = 'annotationSerializerIgnore';

    public $value = Constants::IGNORE_ALWAYS;
}