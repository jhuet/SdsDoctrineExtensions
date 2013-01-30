<?php

namespace Sds\DoctrineExtensions\Test\AccessControl;

use Sds\Common\AccessControl\Constant\Action;
use Sds\Common\AccessControl\Constant\Role;
use Sds\DoctrineExtensions\AccessControl\AccessController;
use Sds\DoctrineExtensions\AccessControl\Events as AccessControlEvents;
use Sds\DoctrineExtensions\AccessControl\DataModel\Permission;
use Sds\DoctrineExtensions\Test\AccessControl\TestAsset\Document\Simple;
use Sds\DoctrineExtensions\Test\AccessControl\TestAsset\Document\StateAware;
use Sds\DoctrineExtensions\Test\BaseTest;

class AccessControlGuestIdentityTest extends BaseTest {

    protected $calls = array();

    public function setUp(){
        parent::setUp();

        $this->configIdentity(true);
        $this->identity->addRole(Role::guest);

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

    public function testGetAllowedActions(){

        $testDoc = new Simple();

        $testDoc->setPermissions(array(
            new Permission(Role::user, Action::create),
            new Permission(Role::guest, Action::read),
            new Permission(Role::guest, Action::update)
        ));

        $this->assertEquals(array('read', 'update'), AccessController::getAllowedActions($testDoc, $this->identity->getRoles()));
    }

    public function testCreateControlNoStateDeny(){

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

        $this->assertTrue(isset($this->calls[AccessControlEvents::createDenied]));

        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertNull($testDoc);
    }

    public function testReadControlNoState(){

        $documentManager = $this->documentManager;

        $filter = $documentManager->getFilterCollection()->enable('readAccessControl');
        $filter->setRoles($this->identity->getRoles());

        $toby = new Simple();
        $toby->setName('toby');
        $toby->setPermissions(array(
            new Permission(Role::guest, Action::create),
            new Permission(Role::user, Action::read)
        ));

        $miriam = new Simple();
        $miriam->setName('miriam');
        $miriam->setPermissions(array(
            new Permission(Role::guest, Action::create),
            new Permission(Role::guest, Action::read)
        ));

        $documentManager->persist($toby);
        $documentManager->persist($miriam);
        $documentManager->flush();
        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($toby));

        $this->assertEquals(array('miriam'), $this->getAllNames($repository));

        $this->identity->addRole(Role::user);
        $filter->setRoles($this->identity->getRoles());

        $documentManager->clear();

        $this->assertEquals(array('miriam', 'toby'), $this->getAllNames($repository));
    }

    public function testReadControlWithState(){

        $documentManager = $this->documentManager;

        $filter = $documentManager->getFilterCollection()->enable('readAccessControl');
        $filter->setRoles($this->identity->getRoles());

        $toby = new StateAware();
        $toby->setName('toby');
        $toby->setState('draft');
        $toby->setPermissions(array(
            new Permission(Role::guest, Action::create, 'draft'),
            new Permission(Role::guest, Action::read, 'draft')
        ));

        $miriam = new StateAware();
        $miriam->setName('miriam');
        $miriam->setState('draft2');
        $miriam->setPermissions(array(
            new Permission(Role::guest, Action::create, 'draft2'),
            new Permission(Role::guest, Action::read, 'draft')
        ));

        $documentManager->persist($toby);
        $documentManager->persist($miriam);
        $documentManager->flush();

        $tobyId = $toby->getId();
        $miriamId = $miriam->getId();

        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($toby));

        $this->assertEquals(array('toby'), $this->getAllNames($repository));

        $documentManager->getFilterCollection()->disable('readAccessControl');

        $miriam = $repository->find($miriamId);
        $miriam->setState('draft');

        $documentManager->flush();
        $documentManager->clear();

        $filter = $documentManager->getFilterCollection()->enable('readAccessControl');
        $filter->setRoles($this->identity->getRoles());

        $this->assertEquals(array('miriam', 'toby'), $this->getAllNames($repository));
    }

    public function testUpdateControlDeny(){
        $this->calls = array();
        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(AccessControlEvents::updateDenied, $this);

        $testDoc = new Simple();
        $testDoc->setName('lucy');
        $testDoc->setPermissions(array(
            new Permission(Role::guest, Action::create),
            new Permission(Role::user, Action::update)
        ));

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($testDoc));

        $testDoc = $repository->find($id);
        $testDoc->setName('changed');

        $documentManager->flush();

        $this->assertTrue(isset($this->calls[AccessControlEvents::updateDenied]));

        $documentManager->clear();
        $testDoc = $repository->find($id);

        $this->assertEquals('lucy', $testDoc->getName());
    }

    public function testUpdateControlGrant(){
        $this->calls = array();
        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(AccessControlEvents::updateDenied, $this);

        $testDoc = new Simple();
        $testDoc->setName('lucy');
        $testDoc->setPermissions(array(
            new Permission(Role::guest, Action::create),
            new Permission(Role::guest, Action::update)
        ));

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($testDoc));

        $testDoc = $repository->find($id);
        $testDoc->setName('changed');

        $documentManager->flush();

        $this->assertFalse(isset($this->calls[AccessControlEvents::updateDenied]));

        $documentManager->clear();
        $testDoc = $repository->find($id);

        $this->assertEquals('changed', $testDoc->getName());
    }

    public function testDeleteControlDeny(){
        $this->calls = array();
        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(AccessControlEvents::deleteDenied, $this);

        $testDoc = new Simple();
        $testDoc->setName('lucy');
        $testDoc->setPermissions(array(
            new Permission(Role::guest, Action::create),
            new Permission(Role::user, Action::delete)
        ));

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($testDoc));

        $testDoc = $repository->find($id);
        $documentManager->remove($testDoc);

        $documentManager->flush();

        $this->assertTrue(isset($this->calls[AccessControlEvents::deleteDenied]));

        $documentManager->clear();
        $testDoc = $repository->find($id);

        $this->assertEquals('lucy', $testDoc->getName());
    }

    public function testDeleteControlGrant(){
        $this->calls = array();
        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $eventManager->addEventListener(AccessControlEvents::deleteDenied, $this);

        $testDoc = new Simple();
        $testDoc->setName('lucy');
        $testDoc->setPermissions(array(
            new Permission(Role::guest, Action::create),
            new Permission(Role::guest, Action::delete)
        ));

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();
        $repository = $documentManager->getRepository(get_class($testDoc));

        $testDoc = $repository->find($id);
        $documentManager->remove($testDoc);

        $documentManager->flush();

        $this->assertFalse(isset($this->calls[AccessControlEvents::deleteDenied]));

        $documentManager->clear();
        $testDoc = $repository->find($id);

        $this->assertNull($testDoc);
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