<?php

namespace Sds\DoctrineExtensions\Test\Validator;

use Sds\DoctrineExtensions\Validator\Events;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\Validator\TestAsset\Document\Simple;

class ValidatorTest extends BaseTest {

    protected $calls = array();

    public function setUp(){

        parent::setUp();

        $manifest = $this->getManifest(array('Sds\DoctrineExtensions\Validator' => ['validateOnFlush' => true]));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array(__NAMESPACE__ . '\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(Events::invalidCreate, $this);
        $eventManager->addEventListener(Events::invalidUpdate, $this);

        $this->calls = array();
    }

    public function testRequired(){
        $documentManager = $this->documentManager;

        $testDoc = new Simple();

        $documentManager->persist($testDoc);
        $documentManager->flush();

        $this->assertTrue(isset($this->calls[Events::invalidCreate]));
        $this->assertFalse(isset($this->calls[Events::invalidUpdate]));
        $this->assertEquals(array('Field name: This value is required.'), $this->calls[Events::invalidCreate][0]->getMessages());

        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertNull($testDoc);
    }

    public function testInvalidCreate(){

        $documentManager = $this->documentManager;

        $testDoc = new Simple();
        $testDoc->setName('invalid');

        $documentManager->persist($testDoc);
        $documentManager->flush();

        $this->assertTrue(isset($this->calls[Events::invalidCreate]));
        $this->assertFalse(isset($this->calls[Events::invalidUpdate]));
        $this->assertEquals(array('Field name: invalid name 1', 'Field name: invalid name 2'), $this->calls[Events::invalidCreate][0]->getMessages());

        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertNull($testDoc);
    }

    public function testValidCreate(){

        $documentManager = $this->documentManager;

        $testDoc = new Simple();
        $testDoc->setName('valid');

        $documentManager->persist($testDoc);
        $documentManager->flush();

        $this->assertFalse(isset($this->calls[Events::invalidCreate]));
        $this->assertFalse(isset($this->calls[Events::invalidUpdate]));

        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertEquals('valid', $testDoc->getName());
    }

    public function testInvalidUpdate() {

        $documentManager = $this->documentManager;

        $testDoc = new Simple();
        $testDoc->setName('valid');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $testDoc->setName('invalid');
        $documentManager->flush();

        $this->assertFalse(isset($this->calls[Events::invalidCreate]));
        $this->assertTrue(isset($this->calls[Events::invalidUpdate]));
        $this->assertEquals(array('Field name: invalid name 1', 'Field name: invalid name 2'), $this->calls[Events::invalidUpdate][0]->getMessages());

        $documentManager->clear();
        $testDoc = $repository->find($id);

        $this->assertEquals('valid', $testDoc->getName());
    }

    public function testValidUpdate() {

        $documentManager = $this->documentManager;

        $testDoc = new Simple();
        $testDoc->setName('valid');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $testDoc->setName('alsoValid');
        $documentManager->flush();

        $this->assertFalse(isset($this->calls[Events::invalidCreate]));
        $this->assertFalse(isset($this->calls[Events::invalidUpdate]));

        $documentManager->clear();
        $testDoc = $repository->find($id);

        $this->assertEquals('alsoValid', $testDoc->getName());
    }

    public function __call($name, $arguments){
        $this->calls[$name] = $arguments;
    }
}