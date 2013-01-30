<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Generator;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Sds\DoctrineExtensions\AnnotationReaderAwareTrait;
use Sds\DoctrineExtensions\AnnotationReaderAwareInterface;
use Sds\DoctrineExtensions\Annotation\Annotations as Sds;
use Sds\DoctrineExtensions\Annotation\AnnotationEventArgs;
use Sds\DoctrineExtensions\Annotation\EventType;

/**
 * Adds generator values to classmetadata
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
        return [
            Sds\Generator::event,
        ];
    }

    /**
     *
     * @param \Doctrine\Common\Annotations\Reader $annotationReader
     * @param string $className
     */
    public function __construct(Reader $annotationReader){
        $this->setAnnotationReader($annotationReader);
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationGenerator(AnnotationEventArgs $eventArgs)
    {
        $parentAnnotation = $eventArgs->getAnnotation();
        if ( ! isset($parentAnnotation->value)){
            return;
        }

        if (! is_array($parentAnnotation->value)){
            $childAnnotations = [$parentAnnotation->value];
        } else {
            $childAnnotations = $parentAnnotation->value;
        }

        $metadata = $eventArgs->getMetadata();
        if ( ! isset($metadata->generator)){
            $metadata->generator = [];
        }

        $eventManager = $eventArgs->getEventManager();

        foreach ($childAnnotations as $annotation){
            if ($annotation instanceof Sds\AbstractGeneratorChild && $eventManager->hasListeners($annotation::event)) {
                $eventManager->dispatchEvent(
                    $annotation::event,
                    new AnnotationEventArgs(
                        $metadata,
                        $eventArgs->getEventType(),
                        $annotation,
                        $eventArgs->getReflection(),
                        $eventManager
                    )
                );
            }
        }
    }
}