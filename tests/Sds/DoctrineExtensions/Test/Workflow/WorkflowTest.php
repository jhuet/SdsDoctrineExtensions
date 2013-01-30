<?php

namespace Sds\DoctrineExtensions\Test\Workflow;

use Sds\DoctrineExtensions\Workflow\Events;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\Workflow\TestAsset\Document\Simple;

class WorkflowTest extends BaseTest {

    protected $calls = array();

    public function setUp(){

        parent::setUp();

        $this->configIdentity();

        $manifest = $this->getManifest(array('Sds\DoctrineExtensions\Workflow' => true));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\Workflow\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
    }

    public function testBasicFunction(){

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(Events::transitionDoesNotExist, $this);

        $testDoc = new Simple();
        $testDoc->setName('version 1');
        $testDoc->setState('draft');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('draft', $testDoc->getState());
        $this->assertEquals(null, $testDoc->getNumStateChanges());

        $testDoc->setState('wrong');

        $documentManager->flush();

        $this->assertEquals('draft', $testDoc->getState());
        $this->assertEquals(null, $testDoc->getNumStateChanges());
        $this->assertTrue(isset($this->calls[Events::transitionDoesNotExist]));

        $testDoc->setState('published');

        $this->calls = array();
        $documentManager->flush();

        $this->assertEquals('draft', $testDoc->getState());
        $this->assertEquals(null, $testDoc->getNumStateChanges());
        $this->assertTrue(isset($this->calls[Events::transitionDoesNotExist]));

        $testDoc->setState('approved');

        $this->calls = array();
        $documentManager->flush();

        $this->assertEquals('approved', $testDoc->getState());
        $this->assertEquals(1, $testDoc->getNumStateChanges());
        $this->assertFalse(isset($this->calls[Events::transitionDoesNotExist]));


        $testDoc->setState('published');

        $this->calls = array();
        $documentManager->flush();

        $this->assertEquals('published', $testDoc->getState());
        $this->assertEquals(2, $testDoc->getNumStateChanges());
        $this->assertFalse(isset($this->calls[Events::transitionDoesNotExist]));
    }

    public function __call($name, $arguments){
        $this->calls[$name] = $arguments;
    }
}