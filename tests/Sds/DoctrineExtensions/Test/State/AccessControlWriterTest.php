<?php

namespace Sds\DoctrineExtensions\Test\State;

use Sds\DoctrineExtensions\Manifest;
use Sds\DoctrineExtensions\State\Events;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\State\TestAsset\Document\AccessControlled;
use Sds\DoctrineExtensions\Test\TestAsset\RoleAwareIdentity;

class AccessControlWriterTest extends BaseTest {

    protected $calls = array();

    public function setUp(){

        $manifest = new Manifest([
            'documents' => [
                __NAMESPACE__ . '\TestAsset\Document' => __DIR__ . '/TestAsset/Document'
            ],
            'extension_configs' => [
                'extension.state' => true,
                'extension.accessControl' => true
            ],
            'document_manager' => 'testing.documentmanager',
            'service_manager_config' => [
                'factories' => [
                    'testing.documentmanager' => 'Sds\DoctrineExtensions\Test\TestAsset\DocumentManagerFactory',
                    'identity' => function(){
                        $identity = new RoleAwareIdentity();
                        $identity->setIdentityName('toby')->addRole('writer');
                        return $identity;
                    }
                ]
            ]
        ]);

        $this->documentManager = $manifest->getServiceManager()->get('testing.documentmanager');
    }

    public function testCreateDeny(){

        $this->calls = array();

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener('createDenied', $this);

        $testDoc = new AccessControlled();

        $testDoc->setName('deny');
        $testDoc->setState('published');

        $documentManager->persist($testDoc);
        $documentManager->flush();

        $this->assertNull($testDoc->getId());
        $this->assertTrue(isset($this->calls['createDenied']));
    }

    public function testTransitionAllow(){

        $this->calls = array();

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(Events::transitionDenied, $this);

        $testDoc = new AccessControlled();

        $testDoc->setName('version 1');
        $testDoc->setState('draft');

        $documentManager->persist($testDoc);
        $documentManager->flush();

        $testDoc->setState('review');

        $documentManager->flush();

        $this->assertEquals('review', $testDoc->getState());
        $this->assertFalse(isset($this->calls[Events::transitionDenied]));
    }

    public function testTransitionDeny(){

        $this->calls = array();

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(Events::transitionDenied, $this);

        $testDoc = new AccessControlled();

        $testDoc->setName('nice doc');
        $testDoc->setState('draft');

        $documentManager->persist($testDoc);
        $documentManager->flush();

        $testDoc->setState('published');

        $documentManager->flush();

        $this->assertEquals('draft', $testDoc->getState());
        $this->assertTrue(isset($this->calls[Events::transitionDenied]));
    }

    public function testTransitionDeny2(){

        $this->calls = array();

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(Events::transitionDenied, $this);

        $testDoc = new AccessControlled();

        $testDoc->setName('nice doc');
        $testDoc->setState('draft');

        $documentManager->persist($testDoc);
        $documentManager->flush();

        $testDoc->setState('review');
        $documentManager->flush();

        $testDoc->setState('published');
        $documentManager->flush();

        $this->assertEquals('review', $testDoc->getState());
        $this->assertTrue(isset($this->calls[Events::transitionDenied]));
    }

    public function testReadAccess(){

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $testDoc = new AccessControlled();

        $testDoc->setName('read doc');
        $testDoc->setState('draft');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $documentManager->clear();

        $testDoc = $documentManager->getRepository(get_class($testDoc))->find($testDoc->getId());
        $this->assertNotNull($testDoc);

        $testDoc->setState('review');
        $documentManager->flush();
        $documentManager->clear();

        $testDoc = $documentManager->getRepository(get_class($testDoc))->find($testDoc->getId());
        $this->assertNull($testDoc);
    }

    public function __call($name, $arguments){
        $this->calls[$name] = $arguments;
    }
}