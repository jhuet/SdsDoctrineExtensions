<?php

namespace Sds\DoctrineExtensions\Test\Freeze;

use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\Freeze\TestAsset\Document\Simple;
use Sds\DoctrineExtensions\Test\Freeze\TestAsset\Subscriber;

class FreezeTest extends BaseTest {

    public function setUp(){

        parent::setUp();

        $this->configIdentity();

        $manifest = $this->getManifest(array('Sds\DoctrineExtensions\Freeze' => true));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\Freeze\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
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

        $this->assertFalse($testDoc->getFrozen());

        $testDoc->freeze();

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($testDoc->getFrozen());

        $testDoc->setName('version 2');

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('version 1', $testDoc->getName());

        $documentManager->remove($testDoc);
        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('version 1', $testDoc->getName());

        $testDoc->thaw();

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($testDoc->getFrozen());
    }

    public function testFilter() {

        $documentManager = $this->documentManager;
        $documentManager->getFilterCollection()->enable('freeze');

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
            $testDocs[0]->freeze();
        } else {
            $testDocs[1]->freeze();
        }

        $documentManager->flush();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('miriam'), $docNames);

        $filter = $documentManager->getFilterCollection()->getFilter('freeze');
        $filter->onlyFrozen();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy'), $docNames);

        $filter->onlyNotFrozen();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('miriam'), $docNames);

        $documentManager->getFilterCollection()->disable('freeze');

        $documentManager->flush();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy', 'miriam'), $docNames);

        if ($testDocs[0]->getName() == 'lucy'){
            $testDocs[0]->thaw();
        } else {
            $testDocs[1]->thaw();
        }

        $documentManager->getFilterCollection()->enable('freeze');

        $documentManager->flush();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy', 'miriam'), $docNames);
    }

    protected function getTestDocs(){
        $repository = $this->documentManager->getRepository('Sds\DoctrineExtensions\Test\Freeze\TestAsset\Document\Simple');
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

        $this->assertFalse($subscriber->getPreFreezeCalled());
        $this->assertFalse($subscriber->getPostFreezeCalled());
        $this->assertFalse($subscriber->getPreThawCalled());
        $this->assertFalse($subscriber->getPostThawCalled());

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertFalse($testDoc->getFrozen());

        $testDoc->freeze();
        $subscriber->reset();

        $documentManager->flush();

        $this->assertTrue($subscriber->getPreFreezeCalled());
        $this->assertTrue($subscriber->getPostFreezeCalled());
        $this->assertFalse($subscriber->getPreThawCalled());
        $this->assertFalse($subscriber->getPostThawCalled());

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($testDoc->getFrozen());

        $testDoc->setName('version 2');
        $subscriber->reset();
        $documentManager->flush();

        $this->assertTrue($subscriber->getFrozenUpdateDeniedCalled());
        $subscriber->reset();

        $documentManager->remove($testDoc);
        $documentManager->flush();

        $this->assertTrue($subscriber->getFrozenDeleteDeniedCalled());

        $documentManager->clear();
        $testDoc = $repository->find($id);

        $testDoc->thaw();
        $subscriber->reset();

        $documentManager->flush();

        $this->assertFalse($subscriber->getPreFreezeCalled());
        $this->assertFalse($subscriber->getPostFreezeCalled());
        $this->assertTrue($subscriber->getPreThawCalled());
        $this->assertTrue($subscriber->getPostThawCalled());

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($testDoc->getFrozen());

        $testDoc->freeze();
        $subscriber->reset();
        $subscriber->setRollbackFreeze(true);

        $documentManager->flush();

        $this->assertTrue($subscriber->getPreFreezeCalled());
        $this->assertFalse($subscriber->getPostFreezeCalled());
        $this->assertFalse($subscriber->getPreThawCalled());
        $this->assertFalse($subscriber->getPostThawCalled());

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertFalse($testDoc->getFrozen());
        $testDoc->freeze();
        $subscriber->reset();
        $documentManager->flush();

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertTrue($testDoc->getFrozen());

        $testDoc->thaw();
        $subscriber->reset();
        $subscriber->setRollbackThaw(true);

        $documentManager->flush();

        $this->assertFalse($subscriber->getPreFreezeCalled());
        $this->assertFalse($subscriber->getPostFreezeCalled());
        $this->assertTrue($subscriber->getPreThawCalled());
        $this->assertFalse($subscriber->getPostThawCalled());
    }
}