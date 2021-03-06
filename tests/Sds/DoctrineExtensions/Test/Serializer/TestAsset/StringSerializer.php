<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Test\Serializer\TestAsset;

use Sds\DoctrineExtensions\Serializer\Type\TypeSerializerInterface;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class StringSerializer implements TypeSerializerInterface {

    public static function serialize($value) {
        return ucfirst($value);
    }

    public static function unserialize($value) {
        return lcfirst($value);
    }
}
