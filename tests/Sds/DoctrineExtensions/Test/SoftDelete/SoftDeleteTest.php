<?php

namespace Sds\DoctrineExtensions\Test\SoftDelete;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Sds\DoctrineExtensions\SoftDelete\Events;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\SoftDelete\TestAsset\Document\Simple;

class SoftDeleteTest extends BaseTest implements EventSubscriber {

    public function setUp(){

        parent::setUp();

        $this->configIdentity();

        $manifest = $this->getManifest(['extensionConfigs' => ['Sds\DoctrineExtensions\SoftDelete' => true]]);

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\SoftDelete\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
        $manifest->setDocumentManagerService($this->documentManager)->bootstrapped();
        $this->softDeleter = $manifest->getServiceManager()->get('softDeleter');
    }

    public function testBasicFunction(){

        $documentManager = $this->documentManager;
        $testDoc = new Simple();

        $testDoc->setName('version 1');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($this->softDeleter->isSoftDeleted($testDoc));

        $this->softDeleter->softDelete($testDoc);

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($this->softDeleter->isSoftDeleted($testDoc));

        $testDoc->setName('version 2');

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('version 1', $testDoc->getName());

        $this->softDeleter->restore($testDoc);

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($this->softDeleter->isSoftDeleted($testDoc));
    }

    public function testFilter() {

        $documentManager = $this->documentManager;
        $documentManager->getFilterCollection()->enable('softDelete');

        $testDocA = new Simple();
        $testDocA->setName('miriam');

        $testDocB = new Simple();
        $testDocB->setName('lucy');

        $documentManager->persist($testDocA);
        $documentManager->persist($testDocB);
        $documentManager->flush();
        $ids = array($testDocA->getId(), $testDocB->getId());
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy', 'miriam'), $docNames);

        if ($testDocs[0]->getName() == 'lucy'){
            $this->softDeleter->softDelete($testDocs[0]);
        } else {
            $this->softDeleter->softDelete($testDocs[1]);
        }

        $documentManager->flush();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('miriam'), $docNames);

        $filter = $documentManager->getFilterCollection()->getFilter('softDelete');
        $filter->onlySoftDeleted();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy'), $docNames);

        $filter->onlyNotSoftDeleted();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('miriam'), $docNames);

        $documentManager->getFilterCollection()->disable('softDelete');

        $documentManager->flush();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy', 'miriam'), $docNames);

        if ($testDocs[0]->getName() == 'lucy'){
            $this->softDeleter->restore($testDocs[0]);
        } else {
            $this->softDeleter->restore($testDocs[1]);
        }

        $documentManager->getFilterCollection()->enable('softDelete');

        $documentManager->flush();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy', 'miriam'), $docNames);
    }

    protected function getTestDocs(){
        $repository = $this->documentManager->getRepository('Sds\DoctrineExtensions\Test\SoftDelete\TestAsset\Document\Simple');
        $testDocs = $repository->findAll();
        $returnDocs = array();
        $returnNames = array();
        foreach ($testDocs as $testDoc){
            $returnDocs[] = $testDoc;
            $returnNames[] = $testDoc->getName();
        }
        sort($returnNames);
        return array($returnDocs, $returnNames);
    }

    public function testEvents() {

        $subscriber = $this;

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();
        $eventManager->addEventSubscriber($subscriber);

        $testDoc = new Simple();

        $testDoc->setName('version 1');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $calls = $subscriber->getCalls();
        $this->assertFalse(isset($calls[Events::preSoftDelete]));
        $this->assertFalse(isset($calls[Events::postSoftDelete]));
        $this->assertFalse(isset($calls[Events::preRestore]));
        $this->assertFalse(isset($calls[Events::postRestore]));

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($this->softDeleter->isSoftDeleted($testDoc));

        $this->softDeleter->softDelete($testDoc);
        $subscriber->reset();

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertTrue(isset($calls[Events::preSoftDelete]));
        $this->assertTrue(isset($calls[Events::postSoftDelete]));
        $this->assertFalse(isset($calls[Events::preRestore]));
        $this->assertFalse(isset($calls[Events::postRestore]));

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($this->softDeleter->isSoftDeleted($testDoc));

        $testDoc->setName('version 2');
        $subscriber->reset();
        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertTrue(isset($calls[Events::softDeletedUpdateDenied]));

        $this->softDeleter->restore($testDoc);
        $subscriber->reset();

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertFalse(isset($calls[Events::preSoftDelete]));
        $this->assertFalse(isset($calls[Events::postSoftDelete]));
        $this->assertTrue(isset($calls[Events::preRestore]));
        $this->assertTrue(isset($calls[Events::postRestore]));

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($this->softDeleter->isSoftDeleted($testDoc));

        $this->softDeleter->softDelete($testDoc);
        $subscriber->reset();
        $subscriber->setRollbackDelete(true);

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertTrue(isset($calls[Events::preSoftDelete]));
        $this->assertFalse(isset($calls[Events::postSoftDelete]));
        $this->assertFalse(isset($calls[Events::preRestore]));
        $this->assertFalse(isset($calls[Events::postRestore]));

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($this->softDeleter->isSoftDeleted($testDoc));
        $this->softDeleter->softDelete($testDoc);
        $subscriber->reset();
        $documentManager->flush();

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($this->softDeleter->isSoftDeleted($testDoc));

        $this->softDeleter->restore($testDoc);
        $subscriber->reset();
        $subscriber->setRollbackRestore(true);

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertFalse(isset($calls[Events::preSoftDelete]));
        $this->assertFalse(isset($calls[Events::postSoftDelete]));
        $this->assertTrue(isset($calls[Events::preRestore]));
        $this->assertFalse(isset($calls[Events::postRestore]));
    }

    protected $calls = array();

    protected $rollbackDelete = false;
    protected $rollbackRestore = false;

    public function getSubscribedEvents(){
        return array(
            Events::preSoftDelete,
            Events::postSoftDelete,
            Events::preRestore,
            Events::postRestore,
            Events::softDeletedUpdateDenied
        );
    }

    public function reset() {
        $this->calls = array();
        $this->rollbackDelete = false;
        $this->rollbackRestore = false;
    }

    public function preSoftDelete(LifecycleEventArgs $eventArgs) {
        $this->calls[Events::preSoftDelete] = $eventArgs;
        if ($this->rollbackDelete) {
            $this->softDeleter->restore($eventArgs->getDocument());
        }
    }

    public function preRestore(LifecycleEventArgs $eventArgs) {
        $this->calls[Events::preRestore] = $eventArgs;
        if ($this->rollbackRestore) {
            $this->softDeleter->softDelete($eventArgs->getDocument());
        }
    }

    public function getRollbackDelete() {
        return $this->rollbackDelete;
    }

    public function setRollbackDelete($rollbackDelete) {
        $this->rollbackDelete = $rollbackDelete;
    }

    public function getRollbackRestore() {
        return $this->rollbackRestore;
    }

    public function setRollbackRestore($rollbackRestore) {
        $this->rollbackRestore = $rollbackRestore;
    }

    public function getCalls() {
        return $this->calls;
    }

    public function __call($name, $arguments){
        $this->calls[$name] = $arguments[0];
    }
}