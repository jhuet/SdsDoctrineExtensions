<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Dojo;

use Sds\DoctrineExtensions\AbstractExtensionConfig;
use Sds\DoctrineExtensions\ClassNamePropertyTrait;

/**
 * Defines the resouces this extension requires
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class ExtensionConfig extends AbstractExtensionConfig {

    use ClassNamePropertyTrait;

    protected $destPaths;

    protected $defaultMixins = [
        'model'                    => ['Sds/Mvc/BaseModel'],
        'form' => [
            'simple'               => ['Sds/Form/Form'],
            'withValidator'        => ['Sds/Form/ValidationControlGroup'],
        ],
        'input' => [
            'string'               => ['Sds/Form/TextBox'],
            'stringWithValidator'  => ['Sds/Form/ValidationTextBox'],
            'float'                => ['Sds/Form/TextBox'],
            'floatWithValidator'   => ['Sds/Form/ValidationTextBox'],
            'int'                  => ['Sds/Form/TextBox'],
            'intWithValidator'     => ['Sds/Form/ValidationTextBox'],
            'boolean'              => ['Sds/Form/Checkbox'],
        ],
        'validator' => [
            'model'       => ['Sds/Validator/Model'],
            'group'       => ['Sds/Validator/Group']
        ],
        'store' => [
            'jsonRest'             => ['Sds/Mvc/JsonRest']
        ]
    ];

    /**
     *
     * @return string
     */
    public function getDestPaths() {
        return $this->destPaths;
    }

    /**
     *
     * @param array $destPath
     */
    public function setDestPaths(array $destPaths) {
        $this->destPaths = $destPaths;
    }

    public function getDefaultMixins() {
        return $this->defaultMixins;
    }

    public function setDefaultMixins(array $defaultMixins) {
        $this->defaultMixins = $defaultMixins;
    }

    /**
     *
     * @var array
     */
    protected $dependencies = array(
        'Sds\DoctrineExtensions\Rest' => true,
        'Sds\DoctrineExtensions\Serializer' => true,
        'Sds\DoctrineExtensions\Validator' => true,
        'Sds\DoctrineExtensions\Generator' => true
    );
}