<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Dojo;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Sds\DoctrineExtensions\AnnotationReaderAwareTrait;
use Sds\DoctrineExtensions\AnnotationReaderAwareInterface;
use Sds\DoctrineExtensions\Annotation\Annotations as Sds;
use Sds\DoctrineExtensions\Annotation\AnnotationEventArgs;
use Sds\DoctrineExtensions\ClassNamePropertyTrait;

/**
 * Adds dojo values to classmetadata
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Subscriber implements EventSubscriber, AnnotationReaderAwareInterface
{
    use AnnotationReaderAwareTrait;
    use ClassNamePropertyTrait;

    /**
     *
     * @return array
     */
    public function getSubscribedEvents(){
        return array(
            Sds\Dojo\Model::event,
            Sds\Dojo\Input::event,
            Sds\Dojo\Form::event,
            Sds\Dojo\Validator::event,
            Sds\Dojo\MultiFieldValidator::event,
            Sds\Dojo\ModelValidator::event,
            Sds\Dojo\JsonRest::event
        );
    }

    /**
     *
     * @param \Doctrine\Common\Annotations\Reader $annotationReader
     * @param string $className
     */
    public function __construct(Reader $annotationReader, $classNameProperty){
        $this->setAnnotationReader($annotationReader);
        $this->setClassNameProperty($classNameProperty);
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationDojoModel(AnnotationEventArgs $eventArgs)
    {
        $annotation = $eventArgs->getAnnotation();
        if ($annotation->generate){
            $eventArgs->getMetadata()->generator[] = [
                'class' => 'Sds\DoctrineExtensions\Dojo\Generator\Model',
                'options' => [
                    'mixins' => $annotation->mixins,
                    'params' => $annotation->params
                ]
            ];
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationDojoForm(AnnotationEventArgs $eventArgs)
    {
        $annotation = $eventArgs->getAnnotation();
        if ($annotation->generate){
            $eventArgs->getMetadata()->generator[] = [
                'class' => 'Sds\DoctrineExtensions\Dojo\Generator\Form',
                'options' => [
                    'mixins' => $annotation->mixins,
                    'params' => $annotation->params
                ]
            ];
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationDojoInput(AnnotationEventArgs $eventArgs)
    {
        $annotation = $eventArgs->getAnnotation();
        if ($annotation->generate){
            $eventArgs->getMetadata()->generator[] = [
                'class' => 'Sds\DoctrineExtensions\Dojo\Generator\Input',
                'options' => [
                    'property' => $eventArgs->getReflection()->name,
                    'mixins' => $annotation->mixins,
                    'params' => $annotation->params
                ]
            ];
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationDojoValidator(AnnotationEventArgs $eventArgs)
    {
        $annotation = $eventArgs->getAnnotation();
        if ($annotation->generate){
            $eventArgs->getMetadata()->generator[] = [
                'class' => 'Sds\DoctrineExtensions\Dojo\Generator\Validator',
                'options' => [
                    'property' => $eventArgs->getReflection()->name,
                    'mixins' => $annotation->mixins,
                    'params' => $annotation->params
                ]
            ];
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationDojoMultiFieldValidator(AnnotationEventArgs $eventArgs)
    {
        $annotation = $eventArgs->getAnnotation();
        if ($annotation->generate){
            $eventArgs->getMetadata()->generator[] = [
                'class' => 'Sds\DoctrineExtensions\Dojo\Generator\MultiFieldValidator',
                'options' => [
                    'mixins' => $annotation->mixins,
                    'params' => $annotation->params
                ]
            ];
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationDojoModelValidator(AnnotationEventArgs $eventArgs)
    {
        $annotation = $eventArgs->getAnnotation();
        if ($annotation->generate){
            $eventArgs->getMetadata()->generator[] = [
                'class' => 'Sds\DoctrineExtensions\Dojo\Generator\ModelValidator',
                'options' => [
                    'mixins' => $annotation->mixins,
                    'params' => $annotation->params
                ]
            ];
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationDojoJsonRest(AnnotationEventArgs $eventArgs)
    {
        $annotation = $eventArgs->getAnnotation();
        if ($annotation->generate){
            $eventArgs->getMetadata()->generator[] = [
                'class' => 'Sds\DoctrineExtensions\Dojo\Generator\JsonRest',
                'options' => [
                    'mixins' => $annotation->mixins,
                    'params' => $annotation->params
                ]
            ];
        }
    }
}