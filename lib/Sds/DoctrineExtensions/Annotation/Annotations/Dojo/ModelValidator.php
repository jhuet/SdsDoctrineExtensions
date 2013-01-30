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
 * The main annotaion for defineing the behaviour of Dojo Model generation.
 * It may be used as a class annotation.
 *
 * @Annotation
 */
final class ModelValidator extends AbstractDojoAnnotation {

    const event = 'annotationDojoModelValidator';

}