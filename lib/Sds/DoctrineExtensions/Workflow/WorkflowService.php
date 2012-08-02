<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Workflow;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Sds\Common\Workflow\WorkflowInterface;
use Sds\DoctrineExtensions\Annotation\Annotations as Sds;
use Sds\DoctrineExtensions\Exception;

/**
 * Workflow helper methods
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class WorkflowService {

    protected static $workflows;

    /**
     *
     * @param \Doctrine\ODM\MongoDB\Mapping\ClassMetadata $metadata
     * @return null|\Sds\DoctrineExtensions\Workflow\workflowClass
     */
    public static function getWorkflow(ClassMetadata $metadata){
        $key = Sds\WorkflowClass::metadataKey;
        if ( ! isset($metadata->$key)) {
            return null;
        }
        $workflowClass = $metadata->$key;

        if (! isset(self::$workflows[$workflowClass]) ) {
            self::$workflows[$workflowClass] = new $workflowClass();
        }
        return self::$workflows[$workflowClass];
    }

    /**
     * Check that a workflow makes sense
     *
     * @param \Sds\Common\Workflow\WorkflowInterface $workflow
     * @throws \Exception
     */
    static public function checkIntegrity(WorkflowInterface $workflow){

        //check that startState is in possibleStates
        if (!(in_array($workflow->getStartState(), $workflow->getPossibleStates()))){
            throw new Exception\BadWorkflowException(sprintf('startState %s is not in possibleStates', $workflow->getStartState()));
        }

        //check that every possibleState can be reached from startState via transitions
        if ($workflow->getPossibleStates() instanceof ArrayCollection) {
            $possibleStates = $workflow->getPossibleStates()->toArray();
        } else {
            $possibleStates = $workflow->getPossibleStates();
        }
        $visitedStates = array($workflow->getStartState());

        if ($workflow->getTransitions() instanceof ArrayCollection) {
            $unusedTransitions = $workflow->getTransitions()->toArray();
        } else {
            $unusedTransitions = $workflow->getTransitions();
        }

        do {
            $visitedCount = count($visitedStates);
            foreach($unusedTransitions as $key => $transition){
                foreach($visitedStates as $state){
                    if($transition->getFromState() == $state &&
                        !in_array($transition->getToState(), $visitedStates)
                    ){
                        $visitedStates[] = $transition->getToState();
                        unset($unusedTransitions[$key]);
                    }
                }
            }
        } while (count($visitedStates) > $visitedCount);

        if (count($visitedStates) != count($possibleStates)){
            throw new Exception\BadWorkflowException('defined transitions do not allow every possible state to be reached');
        }

        // Check for dead transitions
        foreach ($unusedTransitions as $transition) {
            if (!in_array($transition->getFromstate(), $visitedStates)) {
                throw new Exception\BadWorkflowException(sprintf(
                    'Workflow has a dead transition: %s to %s',
                    $transition->getFromState(),
                    $transition->getToState()
                ));
            }
        }

        return true;
    }
}