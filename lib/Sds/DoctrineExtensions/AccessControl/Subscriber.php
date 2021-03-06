<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\AccessControl;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events as ODMEvents;
use Sds\Common\AccessControl\AccessControlledInterface;
use Sds\Common\AccessControl\Constant\Action;
use Sds\Common\State\StateAwareInterface;
use Sds\DoctrineExtensions\AccessControl\AccessController;
use Sds\DoctrineExtensions\AccessControl\Events as AccessControlEvents;
use Sds\DoctrineExtensions\AnnotationReaderAwareTrait;
use Sds\DoctrineExtensions\AnnotationReaderAwareInterface;
use Sds\DoctrineExtensions\Annotation\Annotations as Sds;
use Sds\DoctrineExtensions\Annotation\AnnotationEventArgs;
use Sds\DoctrineExtensions\Freeze\AccessControl\Constant\Action as FreezeAction;
use Sds\DoctrineExtensions\SoftDelete\AccessControl\Constant\Action as SoftDeleteAction;
use Sds\DoctrineExtensions\State\EventArgs as StateEventArgs;
use Sds\DoctrineExtensions\State\Events as StateEvents;
use Sds\DoctrineExtensions\State\Transition;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class Subscriber implements
    EventSubscriber,
    AnnotationReaderAwareInterface
{
    use AnnotationReaderAwareTrait;

    protected $roles;

    public function getRoles() {
        return $this->roles;
    }

    public function setRoles(array $roles = []) {
        $this->roles = $roles;
    }

    /**
     *
     * @return array
     */
    public function getSubscribedEvents(){
        return array(
            Sds\AccessControl::event,
            ODMEvents::onFlush,
            StateEvents::onTransition
        );
    }

    /**
     *
     * @param \Doctrine\Common\Annotations\Reader $annotationReader
     * @param \Sds\Common\AccessControl\IdentityInterface $identity
     */
    public function __construct(
        Reader $annotationReader,
        array $roles = []
    ) {
        $this->setAnnotationReader($annotationReader);
        $this->roles = $roles;
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\Annotation\AnnotationEventArgs $eventArgs
     */
    public function annotationAccessControl(AnnotationEventArgs $eventArgs)
    {
        $annotation = $eventArgs->getAnnotation();

        $accessControlMetadata = [];

        if (is_array($annotation->value)){
            foreach ($annotation->value as $subAnnotation){
                $accessControlMetadata = $this->processAnnotation($subAnnotation, $accessControlMetadata);
            }
        } else {
            $accessControlMetadata = $this->processAnnotation($annotation->value, $accessControlMetadata);
        }

        switch ($eventArgs->getEventType()){
            case 'document':
                $eventArgs->getMetadata()->accessControl['document'] = $accessControlMetadata;
                break;
            case 'property':
                $eventArgs->getMetadata()->accessControl['fields'][$eventArgs->getReflection()->getName()] = $accessControlMetadata;
                break;
        }
    }

    protected function processAnnotation($annotation, $accessControlMetadata){

        switch (true){
            case ($annotation instanceof Sds\AccessControl\DefaultValue):
                $accessControlMetadata['defaultValue'] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\Create):
                $accessControlMetadata[Action::create] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\Read):
                $accessControlMetadata[Action::read] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\Update):
                $accessControlMetadata[Action::update] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\Delete):
                $accessControlMetadata[Action::delete] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\DefaultTransition):
                $accessControlMetadata['defaultTransition'] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\Transition):
                $accessControlMetadata[Transition::getAction($annotation->fromState, $annotation->toState)] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\Freeze):
                $accessControlMetadata[FreezeAction::freeze] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\Thaw):
                $accessControlMetadata[FreezeAction::thaw] = $annotation->value;
                break;
            case ($annotation instanceof Sds\AccessControl\SoftDelete):
                $accessControlMetadata[SoftDeleteAction::softDelete] = $annotation->value;
            case ($annotation instanceof Sds\AccessControl\Restore):
                $accessControlMetadata[SoftDeleteAction::restore] = $annotation->value;
            default:
        }

        return $accessControlMetadata;
    }

    /**
     *
     * @param \Doctrine\ODM\MongoDB\Event\OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $documentManager = $eventArgs->getDocumentManager();
        $unitOfWork = $documentManager->getUnitOfWork();
        $eventManager = $documentManager->getEventManager();

        foreach ($unitOfWork->getScheduledDocumentInsertions() as $document) {

            if(AccessController::isAccessControlEnabled($documentManager->getClassMetadata(get_class($document)), Action::create)){

                //Set stateEqualToParent on permissions
                if ($document instanceof StateAwareInterface) {
                    $documentState = $document->getState();
                    foreach ($document->getPermissions() as $permission){
                        $permission->setStateEqualToParent(($permission->getState() == $documentState));
                    }
                }


                //Check create permissions
                if (! AccessController::isActionAllowed($document, Action::create, $this->roles)) {
                    //stop creation
                    $unitOfWork->detach($document);

                    if ($eventManager->hasListeners(AccessControlEvents::createDenied)) {
                        $eventManager->dispatchEvent(
                            AccessControlEvents::createDenied,
                            new LifecycleEventArgs($document, $documentManager)
                        );
                    }
                }
            }
        }

        //Check update permissions
        foreach ($unitOfWork->getScheduledDocumentUpdates() as $document) {

            $metadata = $documentManager->getClassMetadata(get_class($document));
            if(AccessController::isAccessControlEnabled($metadata, Action::update)){

                // Skip any updates on fields marked with @AccessControl(@Update = false)
                $changeSet = $unitOfWork->getDocumentChangeSet($document);

                $checkPermission = false;
                foreach ($changeSet as $field => $change) {
                    if ( ! isset($metadata->accessControl['fields'][$field][Action::update]) ||
                        $metadata->accessControl['fields'][$field][Action::update]
                    ) {
                        $checkPermission = true;
                        break;
                    }
                }
                if (!$checkPermission){
                    continue;
                }

                // allow updates to @stateField. If you need to control state updates, enable
                // access control in the state extension
                if ($document instanceof StateAwareInterface) {

                    $changeSet = $unitOfWork->getDocumentChangeSet($document);
                    $metadata = $documentManager->getClassMetadata(get_class($document));
                    $field = $metadata->stateField;

                    if (count($changeSet) == 1 && isset($changeSet[$field])) {
                        continue;
                    }
                }

                if (!AccessController::isActionAllowed($document, Action::update, $this->roles)) {
                    //stop updates
                    $unitOfWork->clearDocumentChangeSet(spl_object_hash($document));

                    if ($eventManager->hasListeners(AccessControlEvents::updateDenied)) {
                        $eventManager->dispatchEvent(
                            AccessControlEvents::updateDenied,
                            new LifecycleEventArgs($document, $documentManager)
                        );
                    }
                }
            }
        }

        //Check delete permsisions
        foreach ($unitOfWork->getScheduledDocumentDeletions() as $document) {
            if (AccessController::isAccessControlEnabled($documentManager->getClassMetadata(get_class($document)), Action::delete) &&
                !AccessController::isActionAllowed($document, Action::delete, $this->roles)
            ) {
                //stop delete
                $documentManager->persist($document);

                if ($eventManager->hasListeners(AccessControlEvents::deleteDenied)) {
                    $eventManager->dispatchEvent(
                        AccessControlEvents::deleteDenied,
                        new LifecycleEventArgs($document, $documentManager)
                    );
                }
            }
        }
    }

    /**
     *
     * @param \Sds\DoctrineExtensions\State\Event\EventArgs $eventArgs
     */
    public function onTransition(StateEventArgs $eventArgs){
        $document = $eventArgs->getDocument();

        if($document instanceof AccessControlledInterface) {
            $toState = $eventArgs->getTransition()->getToState();
            foreach ($document->getPermissions() as $permission){
                $permission->setStateEqualToParent(($permission->getState() == $toState));
            }
        }
    }
}