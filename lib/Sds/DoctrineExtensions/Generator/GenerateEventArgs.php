<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Generator;

use Doctrine\Common\EventArgs as BaseEventArgs;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * Arguments for generate events
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GenerateEventArgs extends BaseEventArgs {

    protected $metadata;

    protected $documentManager;

    protected $eventManager;

    protected $results;

    protected $options;

    /**
     *
     * @param \Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo $metadata
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @param \Doctrine\Common\EventManager $eventManager
     * @param \ArrayObject $results
     * @param array $options
     */
    public function __construct(
        ClassMetadataInfo $metadata,
        DocumentManager $documentManager,
        EventManager $eventManager,
        \ArrayObject $results,
        array $options = []
    ) {
        $this->metadata = $metadata;
        $this->documentManager = $documentManager;
        $this->eventManager = $eventManager;
        $this->results = $results;
        $this->options = $options;
    }

    public function getMetadata() {
        return $this->metadata;
    }

    public function getDocumentManager() {
        return $this->documentManager;
    }

    public function getEventManager() {
        return $this->eventManager;
    }

    public function getResults() {
        return $this->results;
    }

    public function getOptions() {
        return $this->options;
    }

}