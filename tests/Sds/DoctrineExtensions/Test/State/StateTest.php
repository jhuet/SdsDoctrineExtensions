<?php

namespace Sds\DoctrineExtensions\Test\State;

use Sds\DoctrineExtensions\State\Events;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\State\TestAsset\Document\Simple;
use Sds\DoctrineExtensions\Test\State\TestAsset\Subscriber;

class StateTest extends BaseTest {

    public function setUp(){

        parent::setUp();

        $manifest = $this->getManifest(['extensionConfigs' => ['Sds\DoctrineExtensions\State' => true]]);

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\State\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
        $manifest->setDocumentManagerService($this->documentManager)->bootstrapped();
    }

    public function testBasicFunction(){

        $documentManager = $this->documentManager;
        $testDoc = new Simple();

        $testDoc->setName('version 1');
        $testDoc->setState('state1');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('state1', $testDoc->getState());

        $testDoc->setState('state2');

        $documentManager->flush();
        $documentManager->clear();
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('state2', $testDoc->getState());
    }

    public function testFilter() {

        $documentManager = $this->documentManager;
        $documentManager->getFilterCollection()->enable('state');

        $testDocA = new Simple();
        $testDocA->setName('miriam');
        $testDocA->setState('active');

        $testDocB = new Simple();
        $testDocB->setName('lucy');
        $testDocB->setState('inactive');

        $documentManager->persist($testDocA);
        $documentManager->persist($testDocB);
        $documentManager->flush();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy', 'miriam'), $docNames);


        $documentManager->flush();
        $documentManager->clear();

        $filter = $documentManager->getFilterCollection()->getFilter('state');
        $filter->addState('active');

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('miriam'), $docNames);

        $filter->excludeStateList();
        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy'), $docNames);

        $documentManager->getFilterCollection()->disable('state');

        $documentManager->clear();

        list($testDocs, $docNames) = $this->getTestDocs();
        $this->assertEquals(array('lucy', 'miriam'), $docNames);
    }

    protected function getTestDocs(){
        $repository = $this->documentManager->getRepository('Sds\DoctrineExtensions\Test\State\TestAsset\Document\Simple');
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
        $testDoc->setState('state1');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $calls = $subscriber->getCalls();
        $this->assertFalse(isset($calls[Events::preTransition]));
        $this->assertFalse(isset($calls[Events::onTransition]));
        $this->assertFalse(isset($calls[Events::postTransition]));

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $testDoc->setState('state2');
        $subscriber->reset();

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertTrue(isset($calls[Events::preTransition]));
        $this->assertTrue(isset($calls[Events::onTransition]));
        $this->assertTrue(isset($calls[Events::postTransition]));

        $documentManager->clear();
        $testDoc = $repository->find($id);

        $testDoc->setState('state3');
        $subscriber->reset();
        $subscriber->setRollbackTransition(true);

        $documentManager->flush();

        $calls = $subscriber->getCalls();
        $this->assertTrue(isset($calls[Events::preTransition]));
        $this->assertFalse(isset($calls[Events::onTransition]));
        $this->assertFalse(isset($calls[Events::postTransition]));

        $this->assertEquals('state2', $testDoc->getState());
    }
}