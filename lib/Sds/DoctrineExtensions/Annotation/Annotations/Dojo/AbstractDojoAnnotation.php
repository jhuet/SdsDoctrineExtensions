<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Annotation\Annotations\Dojo;

use Sds\DoctrineExtensions\Annotation\Annotations\AbstractGeneratorChild;

/**
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 *
 */
abstract class AbstractDojoAnnotation extends AbstractGeneratorChild {

    /**
     * Should a dojo module be generated?
     *
     * @var boolean
     */
    public $generate = true;

    /**
     * Which dojo modules should be mixed together to make this module?
     *
     * @var array
     */
    public $mixins;

    /**
     * What parameters should be added to this module?
     *
     * @var array
     */
    public $params;
}