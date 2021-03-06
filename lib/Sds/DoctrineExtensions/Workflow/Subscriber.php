<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Workflow;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Event\PreFlushEventArgs;
use Doctrine\ODM\MongoDB\Events as ODMEvents;
use Sds\Common\Workflow\WorkflowAwareInterface;
use Sds\DoctrineExtensions\Annotation\Annotations as Sds;
use Sds\DoctrineExtensions\Annotation\AnnotationEventArgs;
use Sds\DoctrineExtensions\State\Events as StateEvents;
use Sds\DoctrineExtensions\State\EventArgs as TransitionEventArgs;
use Sds\DoctrineExtensions\Workflow\Events as WorkflowEvents;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Subscriber implements EventSubscriber
{

    protected $workflows = [];

    /**
     *
     * @return array
     */
    public function getSubscribedEvents(){
        return array(
            Sds\Workflow::event,
            ODMEvents::preFlush,
            StateEvents::preTransition,
            StateEvents::onTransition
        );
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationWorkflow(AnnotationEventArgs $eventArgs) {
        $eventArgs->getMetadata()->workflow = $eventArgs->getAnnotation()->value;
    }

    /**
     *
     * @param \Doctrine\ODM\MongoDB\Event\PreFlushEventArgs $eventArgs
     */
    public function preFlush(PreFlushEventArgs $eventArgs) {
        $documentManager = $eventArgs->getDocumentManager();
        $unitOfWork = $documentManager->getUnitOfWork();

        //Set startState for documents with workflow
        foreach ($unitOfWork->getScheduledDocumentInsertions() as $document) {
            if (!$document instanceof WorkflowAwareInterface){
                continue;
            }
            $document->setState($this->getWorkflow($documentManager->getClassMetadata(get_class($document)))->getStartState());
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\State\Event\EventArgs $eventArgs
     */
    public function preTransition(TransitionEventArgs $eventArgs) {
        $document = $eventArgs->getDocument();

        if (!$document instanceof WorkflowAwareInterface){
            return;
        }

        $fromState = $eventArgs->getTransition()->getFromState();
        $toState = $eventArgs->getTransition()->getToState();

        foreach ($this->getWorkflow($eventArgs->getDocumentManager()->getClassMetadata(get_class($document)))->getTransitions() as $transition){
            if ($transition->getFromState() == $fromState &&
                $transition->getToState() == $toState
            ) {
                // Transition extists. State change ok.
                return;
            }
        }

        // Transition does not exist. Roll back state change
        $document->setState($fromState);

        // Raise transitionDoesNotExist
        $eventManager = $eventArgs->getDocumentManager()->getEventManager();
        if ($eventManager->hasListeners(WorkflowEvents::transitionDoesNotExist)) {
            $eventManager->dispatchEvent(
                WorkflowEvents::transitionDoesNotExist,
                $eventArgs
            );
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\State\Event\EventArgs $eventArgs
     */
    public function onTransition(TransitionEventArgs $eventArgs) {

        $document = $eventArgs->getDocument();

        if (!$document instanceof WorkflowAwareInterface){
            return;
        }

        // Update workflow
        $this->getWorkflow($eventArgs->getDocumentManager()->getClassMetadata(get_class($document)))->update($document);
    }

    /**
     *
     * @param \Doctrine\ODM\MongoDB\Mapping\ClassMetadata $metadata
     * @return null|\Sds\Common\Workflow\WorkflowInterface
     */
    protected function getWorkflow(ClassMetadata $metadata){
        if ( ! isset($metadata->workflow)) {
            return null;
        }
        $workflowClass = $metadata->workflow;

        if (! isset($this->workflows[$workflowClass]) ) {
            $this->workflows[$workflowClass] = new $workflowClass();
        }
        return $this->workflows[$workflowClass];
    }
}