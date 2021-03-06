<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\State;

use Sds\Common\AccessControl\AccessControlIdentityInterface;
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

        $this->subscribers = array(new Subscriber($config->getAnnotationReader()));
        if ($config->getEnableAccessControl()){
            $this->subscribers[] = new AccessControl\TransitionSubscriber($config->getRoles());
        }

        $this->filters = array('state' => 'Sds\DoctrineExtensions\State\Filter\State');
    }

    public function setIdentity($identity){
        parent::setIdentity($identity);
        foreach ($this->subscribers as $subscriber){
            switch (true){
                case $subscriber instanceof AccessControl\TransitionSubscriber:
                    $subscriber->setRoles($identity->getRoles());
                    break;
            }
        }
    }
}
