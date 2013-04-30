<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions;

use Doctrine\ODM\MongoDB\DocumentManager;

trait DocumentManagerAwareTrait
{

    protected $documentManager = null;

    public function setDocumentManager(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;

        return $this;
    }

    public function getDocumentManager()
    {
        return $this->documentManager;
    }
}
