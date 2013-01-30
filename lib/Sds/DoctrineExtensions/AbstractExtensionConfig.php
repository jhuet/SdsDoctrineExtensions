<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions;

use Doctrine\Common\Annotations\Reader;
use Sds\DoctrineExtensions\Exception;

/**
 * A base class which extensions configs must extend
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
abstract class AbstractExtensionConfig {

    protected $identity;

    /**
     * List of other extensions which must be loaded
     * for this extension to work
     *
     * @var array
     */
    protected $dependencies = array('Sds\DoctrineExtensions\Annotation' => null);


    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    protected $annotationReader;

    /**
     *
     * @return \Doctrine\Common\Annotations\Reader
     */
    public function getAnnotationReader() {
        return $this->annotationReader;
    }

    /**
     *
     * @param \Doctrine\Common\Annotations\Reader $annoationReader
     */
    public function setAnnotationReader(Reader $annotationReader) {
        $this->annotationReader = $annotationReader;
    }

    public function getIdentity() {
        return $this->identity;
    }

    public function setIdentity($identity) {
        $this->identity = $identity;
    }

    /**
     *
     * @return array
     */
    public function getDependencies() {
        return $this->dependencies;
    }

    /**
     *
     * @param array $dependencies
     */
    public function setDependencies(array $dependencies) {
        $this->dependencies = $dependencies;
    }

    /**
     *
     * @param string $namespace
     * @param boolean | \Sds\DoctrineExtensions\AbstractExtensionConfig $config
     */
    public function addDependency($namespace, $config = true){
        $this->dependencies[$namespace] = $config;
    }

    /**
     *
     * @param string $namespace
     */
    public function removeDependency($namespace){
        unset($this->dependencies[$namespace]);
    }

    /**
     * @param  array|Traversable|null $options
     * @return AbstractOptions
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setFromArray($options);
        }
    }

    /**
     * @param  array|Traversable $options
     * @return void
     */
    public function setFromArray($options)
    {
        if (!is_array($options) && !$options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Options provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }

        foreach ($options as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $setter = $this->assembleSetterNameFromKey($key);
        $this->{$setter}($value);
    }

    /**
     * @param string $key name of option with underscore
     * @return string name of setter method
     * @throws Exception\BadMethodCallException if setter method is undefined
     */
    protected function assembleSetterNameFromKey($key)
    {
        $parts = explode('_', $key);
        $parts = array_map('ucfirst', $parts);
        $setter = 'set' . implode('', $parts);
        if (!method_exists($this, $setter)) {
            throw new Exception\BadMethodCallException(
                'The option "' . $key . '" does not '
                . 'have a matching ' . $setter . ' setter method '
                . 'which must be defined'
            );
        }
        return $setter;
    }
}
