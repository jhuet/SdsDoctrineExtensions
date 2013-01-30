<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Dojo;

use Sds\DoctrineExtensions\AbstractExtension;
use Sds\DoctrineExtensions\Dojo\Generator;

/**
 * Defines the resouces this extension provies
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Extension extends AbstractExtension {

    public function __construct($config){

        $this->configClass = __NAMESPACE__ . '\ExtensionConfig';
        parent::__construct($config);
        $config = $this->getConfig();

        $this->subscribers = [
            new Subscriber(
                $config->getAnnotationReader(),
                $config->getClassNameProperty()
            ),
            new Generator\Form(
                $config->getDestPaths(),
                $config->getDefaultMixins()
            ),
            new Generator\Input(
                $config->getDestPaths(),
                $config->getDefaultMixins()
            ),
            new Generator\MultiFieldValidator(
                $config->getDestPaths(),
                $config->getDefaultMixins()
            ),
            new Generator\Validator(
                $config->getDestPaths(),
                $config->getDefaultMixins()
            ),
            new Generator\Model(
                $config->getDestPaths(),
                $config->getDefaultMixins()
            ),
            new Generator\ModelValidator(
                $config->getDestPaths(),
                $config->getDefaultMixins()
            ),
            new Generator\JsonRest(
                $config->getDestPaths(),
                $config->getDefaultMixins()
            )
        ];
    }
}
