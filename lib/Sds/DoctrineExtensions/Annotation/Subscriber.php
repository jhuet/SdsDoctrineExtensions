<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Annotation;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Events as ODMEvents;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Sds\DoctrineExtensions\AnnotationReaderAwareTrait;
use Sds\DoctrineExtensions\AnnotationReaderAwareInterface;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Subscriber implements EventSubscriber, AnnotationReaderAwareInterface
{
    use AnnotationReaderAwareTrait;

    /**
     *
     * @return array
     */
    public function getSubscribedEvents(){
        return array(
            ODMEvents::loadClassMetadata
        );
    }

    /**
     *
     * @param \Doctrine\Common\Annotations\Reader $annotationReader
     * @param \Sds\Validator\ValidatorInterface $validator
     */
    public function __construct(Reader $annotationReader){
        $this->setAnnotationReader($annotationReader);
    }

    /**
     *
     * @param \Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();
        $documentManager = $eventArgs->getDocumentManager();
        $eventManager = $documentManager->getEventManager();


        //Inherit document annotations from parent classes
        if (count($metadata->parentClasses) > 0) {
            foreach ($metadata->parentClasses as $parentClass) {
                $this->buildMetadata($documentManager->getClassMetadata($parentClass), $metadata, $eventManager);
            }
        }

        $this->buildMetadata($metadata, $metadata, $eventManager);
    }

    protected function buildMetadata(ClassMetadata $source, ClassMetadata $target, $eventManager){

        $sourceReflClass = $source->getReflectionClass();
        $targetReflClass = $target->getReflectionClass();

        //Document annotations
        foreach ($this->annotationReader->getClassAnnotations($sourceReflClass) as $annotation) {
            if (defined(get_class($annotation) . '::event')) {

                // Raise annotation event
                if ($eventManager->hasListeners($annotation::event)) {
                    $eventManager->dispatchEvent(
                        $annotation::event,
                        new AnnotationEventArgs($target, EventType::document, $annotation, $targetReflClass, $eventManager)
                    );
                }
            }
        }

        //Property annotations
        foreach ($sourceReflClass->getProperties() as $reflProperty) {
            foreach ($this->annotationReader->getPropertyAnnotations($reflProperty) as $annotation) {
                if (defined(get_class($annotation) . '::event')) {

                    // Raise annotation event
                    if ($eventManager->hasListeners($annotation::event)) {
                        $eventManager->dispatchEvent(
                            $annotation::event,
                            new AnnotationEventArgs($target, EventType::property, $annotation, $reflProperty, $eventManager)
                        );
                    }
                }
            }
        }

        //Method annotations
        foreach ($sourceReflClass->getMethods() as $reflMethod) {

            foreach ($this->annotationReader->getMethodAnnotations($reflMethod) as $annotation) {
                if (defined(get_class($annotation) . '::event')) {

                    // Raise annotation event
                    if ($eventManager->hasListeners($annotation::event)) {
                        $eventManager->dispatchEvent(
                            $annotation::event,
                            new AnnotationEventArgs($target, EventType::method, $annotation, $reflMethod, $eventManager)
                        );
                    }
                }
            }
        }
    }
}
