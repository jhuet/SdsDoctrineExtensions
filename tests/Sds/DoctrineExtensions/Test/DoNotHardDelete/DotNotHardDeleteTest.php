<?php

namespace Sds\DoctrineExtensions\Test\DoNotHardDelete;

use Sds\DoctrineExtensions\DoNotHardDelete\Events;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\DoNotHardDelete\TestAsset\Document\Deleteable;
use Sds\DoctrineExtensions\Test\DoNotHardDelete\TestAsset\Document\NotDeleteable;

class DoNotHardDeleteTest extends BaseTest {

    protected $calls = array();

    public function setUp(){

        parent::setUp();
        $manifest = $this->getManifest(array('Sds\DoctrineExtensions\DoNotHardDelete' => true));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\DoNotHardDelete\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
    }

    public function testAllowDelete(){

        $documentManager = $this->documentManager;

        $testDoc = new Deleteable();
        $testDoc->setName('go');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertNotNull($testDoc);
        $this->assertEquals('go', $testDoc->getName());

        $documentManager->remove($testDoc);
        $documentManager->flush();
        $documentManager->clear();

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertNull($testDoc);
    }

    public function testDenyDelete(){

        $documentManager = $this->documentManager;

        $testDoc = new NotDeleteable();
        $testDoc->setName('stay');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertNotNull($testDoc);
        $this->assertEquals('stay', $testDoc->getName());

        $documentManager->remove($testDoc);
        $documentManager->flush();
        $documentManager->clear();

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertNotNull($testDoc);
        $this->assertEquals('stay', $testDoc->getName());
    }

    public function testEventCalled() {

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();
        $eventManager->addEventListener(Events::hardDeleteDenied, $this);

        $testDoc = new NotDeleteable();
        $testDoc->setName('stay');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = null;
        $testDoc = $repository->find($id);

        $documentManager->remove($testDoc);

        $this->assertFalse(isset($this->calls[Events::hardDeleteDenied]));
        $documentManager->flush();
        $this->assertTrue(isset($this->calls[Events::hardDeleteDenied]));
    }

    public function __call($name, $arguments){
        $this->calls[$name] = $arguments;
    }
}