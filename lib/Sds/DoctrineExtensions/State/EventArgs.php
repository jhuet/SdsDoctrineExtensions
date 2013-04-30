<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\State;

use Doctrine\Common\EventArgs as BaseEventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use Sds\Common\State\Transition;

/**
 * Arguments for transition events
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class EventArgs extends BaseEventArgs {

    /**
     *
     * @var Transition
     */
    protected $transition;

    /**
     * The document with the changed state
     *
     * @var object
     */
    protected $document;

    /**
     *
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $documentManager;

    /**
     *
     * @param \Sds\Common\State\Transition $transition
     * @param object $document
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     */
    public function __construct(
        Transition $transition,
        $document,
        DocumentManager $documentManager
    ) {
        $this->transition = $transition;
        $this->document = $document;
        $this->documentManager = $documentManager;
    }

    /**
     *
     * @return \Sds\Common\State\Transition
     */
    public function getTransition() {
        return $this->transition;
    }

    /**
     *
     * @return object
     */
    public function getDocument() {
        return $this->document;
    }

    /**
     *
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    public function getDocumentManager() {
        return $this->documentManager;
    }
}