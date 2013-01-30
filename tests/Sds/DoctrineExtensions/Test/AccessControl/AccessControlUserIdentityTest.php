<?php

namespace Sds\DoctrineExtensions\Test\AccessControl;

use Sds\Common\AccessControl\Constant\Action;
use Sds\Common\AccessControl\Constant\Role;
use Sds\DoctrineExtensions\AccessControl\Events as AccessControlEvents;
use Sds\DoctrineExtensions\AccessControl\DataModel\Permission;
use Sds\DoctrineExtensions\Test\AccessControl\TestAsset\Document\Simple;
use Sds\DoctrineExtensions\Test\AccessControl\TestAsset\Document\StateAware;
use Sds\DoctrineExtensions\Test\BaseTest;

class AccessControlUserIdentityTest extends BaseTest {

    protected $calls = array();

    public function setUp(){
        parent::setUp();

        $this->configIdentity(true);
        $this->identity->addRole(Role::user);

        $manifest = $this->getManifest(array('Sds\DoctrineExtensions\AccessControl' => true));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\AccessControl\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
    }

    public function testCreateControlNoStateGrant(){

        $this->calls = array();
        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(AccessControlEvents::createDenied, $this);

        $testDoc = new Simple();

        $testDoc->setPermissions(array(
            new Permission(Role::user, Action::create)
        ));

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();

        $this->assertFalse(isset($this->calls[AccessControlEvents::createDenied]));

        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertEquals($id, $testDoc->getId());
    }

    public function testCreateControlWithStateDeny(){

        $this->calls = array();
        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(AccessControlEvents::createDenied, $this);

        $testDoc = new StateAware();

        $testDoc->setPermissions(array(
            new Permission(Role::user, Action::create, 'new')
        ));
        $testDoc->setState('notWork');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();

        $this->assertTrue(isset($this->calls[AccessControlEvents::createDenied]));

        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertNull($testDoc);
    }

    public function testCreateControlWithStateGrant(){

        $this->calls = array();
        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(AccessControlEvents::createDenied, $this);

        $testDoc = new StateAware();

        $testDoc->setPermissions(array(
            new Permission(Role::user, Action::create, 'new')
        ));
        $testDoc->setState('new');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();

        $this->assertFalse(isset($this->calls[AccessControlEvents::createDenied]));

        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertEquals($id, $testDoc->getId());
    }

    protected function getAllNames($repository){
        $names = array();
        $documents = $repository->findAll();
        foreach ($documents as $document){
            $names[] = $document->getName();
        }
        sort($names);
        return $names;
    }

    public function __call($name, $arguments){
        $this->calls[$name] = $arguments;
    }
}