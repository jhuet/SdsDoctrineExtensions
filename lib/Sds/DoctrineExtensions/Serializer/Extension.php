<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Serializer;

use Sds\DoctrineExtensions\AbstractExtension;

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

        $this->subscribers = array(new Subscriber(
            $config->getAnnotationReader(),
            $config->getClassNameProperty()
        ));

        Serializer::addTypeSerializer('date', 'Sds\DoctrineExtensions\Serializer\Type\DateToISO8601');
        Serializer::setMaxNestingDepth($config->getMaxNestingDepth());

        foreach ($config->getTypeSerializers() as $type => $typeSerializer){
            Serializer::addTypeSerializer($type, $typeSerializer);
        }
    }
}
