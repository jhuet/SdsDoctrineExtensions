<?php

namespace Sds\DoctrineExtensions\Test\SoftDelete;

use Sds\DoctrineExtensions\SoftDelete\Events;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\SoftDelete\TestAsset\Document\Simple;
use Sds\DoctrineExtensions\Test\SoftDelete\TestAsset\Subscriber;

class SoftDeleteTest extends BaseTest {

    public function setUp(){

        parent::setUp();

        $this->configIdentity();

        $manifest = $this->getManifest(array('Sds\DoctrineExtensions\SoftDelete' => true));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\SoftDelete\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
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

        $this->assertFalse($testDoc->getSoftDeleted());

        $testDoc->softDelete();

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($testDoc->getSoftDeleted());

        $testDoc->setName('version 2');

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('version 1', $testDoc->getName());

        $testDoc->restore();

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($testDoc->getSoftDeleted());
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
            $testDocs[0]->softDelete();
        } else {
            $testDocs[1]->softDelete();
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
            $testDocs[0]->restore();
        } else {
            $testDocs[1]->restore();
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

        $subscriber = new Subscriber();

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

        $this->assertFalse($testDoc->getSoftDeleted());

        $testDoc->softDelete();
        $subscriber->reset();

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertTrue(isset($calls[Events::preSoftDelete]));
        $this->assertTrue(isset($calls[Events::postSoftDelete]));
        $this->assertFalse(isset($calls[Events::preRestore]));
        $this->assertFalse(isset($calls[Events::postRestore]));

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($testDoc->getSoftDeleted());

        $testDoc->setName('version 2');
        $subscriber->reset();
        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertTrue(isset($calls[Events::softDeletedUpdateDenied]));

        $testDoc->restore();
        $subscriber->reset();

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertFalse(isset($calls[Events::preSoftDelete]));
        $this->assertFalse(isset($calls[Events::postSoftDelete]));
        $this->assertTrue(isset($calls[Events::preRestore]));
        $this->assertTrue(isset($calls[Events::postRestore]));

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($testDoc->getSoftDeleted());

        $testDoc->softDelete();
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

        $this->assertFalse($testDoc->getSoftDeleted());
        $testDoc->softDelete();
        $subscriber->reset();
        $documentManager->flush();

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($testDoc->getSoftDeleted());

        $testDoc->restore();
        $subscriber->reset();
        $subscriber->setRollbackRestore(true);

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertFalse(isset($calls[Events::preSoftDelete]));
        $this->assertFalse(isset($calls[Events::postSoftDelete]));
        $this->assertTrue(isset($calls[Events::preRestore]));
        $this->assertFalse(isset($calls[Events::postRestore]));
    }
}