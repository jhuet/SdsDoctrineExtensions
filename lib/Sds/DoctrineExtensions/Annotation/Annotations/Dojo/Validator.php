<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Annotation\Annotations\Dojo;

/**
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 *
 * The main annotaion for defineing the behaviour of Dojo Validator generation.
 * It may be used as a property annotation.
 *
 * @Annotation
 */
final class Validator extends AbstractDojoAnnotation {

    const event = 'annotationDojoValidator';

}