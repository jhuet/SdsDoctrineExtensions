<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Annotation\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 *
 * The main annotaion for defineing the behaviour of Dojo Form generation.
 *
 * The following annotaions are permissable only in a class context
 *     Ignore
 *     Mixin
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class DojoForm extends Annotation {

    const event = 'annotationDojoForm';
}