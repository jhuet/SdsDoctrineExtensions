<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Serializer\Reference;

use Sds\DoctrineExtensions\DocumentManagerAwareInterface;
use Sds\DoctrineExtensions\DocumentManagerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Eager implements ReferenceSerializerInterface, ServiceLocatorAwareInterface, DocumentManagerAwareInterface {

    use ServiceLocatorAwareTrait;
    use DocumentManagerAwareTrait;

    protected $serializer;

    public function serialize($id, array $mapping){

        $document = $this->documentManager->getRepository($mapping['targetDocument'])->find($id);
        if ($document){
            return $this->getSerializer()->toArray($document);
        } else {
            return null;
        }
    }

    protected function getSerializer(){
        if (!isset($this->serializer)){
            $this->serializer = $this->serviceLocator->get('serializer');
        }
        return $this->serializer;
    }
}
