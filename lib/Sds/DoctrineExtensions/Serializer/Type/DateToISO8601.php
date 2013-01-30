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
class DateToISO8601 implements TypeSerializerInterface {

    public static function serialize($value) {

        switch (true){
            case $value instanceof \MongoDate:
                $value = new \DateTime("@$value->sec");
            case $value instanceof \DateTime:
                $value->setTimezone(new \DateTimeZone('UTC'));
                return $value->format('c');
                break;
        }
    }

    public static function unserialize($value) {
        return new \DateTime($value);
    }
}
