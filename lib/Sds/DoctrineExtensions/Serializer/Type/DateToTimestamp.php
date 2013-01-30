<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Serializer\Type;

/**
 * Serializes dateTime objects
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DateSerializer implements TypeSerializerInterface {

    public static function serialize($value) {

        switch (true){
            case $value instanceof \MongoDate:
                return $value->sec;
                break;
            case $value instanceof \DateTime:
                return (integer) $value->format('U');
                break;
        }
    }

    public static function unserialize($value) {
        return new \DateTime("@$value");
    }
}
