<?php

namespace Sds\DoctrineExtensions\Test\Serializer;

use Sds\Common\AccessControl\Constant\Action;
use Sds\Common\AccessControl\Constant\Role;
use Sds\DoctrineExtensions\AccessControl\DataModel\Permission;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Serializer\Serializer;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeWithSecrets;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\SecretIngredient;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Ingredient;

class SerializerAccessControlledReferenceTest extends BaseTest {

    public function setUp(){

        parent::setUp();

        $this->configIdentity(true);
        $this->identity->addRole(Role::guest);

        $manifest = $this->getManifest([
            'Sds\DoctrineExtensions\AccessControl' => true,
            'Sds\DoctrineExtensions\Serializer' => true
        ]);

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
    }

    public function testSerialize(){

        $documentManager = $this->documentManager;

        //Turn on access control read filter
        $filter = $documentManager->getFilterCollection()->enable('readAccessControl');
        $filter->setRoles($this->identity->getRoles());

        //bake the cake. Hmm yum.
        $cake = new CakeWithSecrets();
        $cake->setIngredients([
            new Ingredient('flour'),
            new Ingredient('sugar')
        ]);

        $chocolate = new SecretIngredient('chocolate');
        $chocolate->setPermissions([
            new Permission(Role::guest, Action::create),
            new Permission(Role::user, Action::read)
        ]);

        $strawberry = new SecretIngredient('strawberry');
        $strawberry->setPermissions([
            new Permission(Role::guest, Action::create),
            new Permission(Role::guest, Action::read)
        ]);

        $cake->setSecretIngredients([
            $chocolate,
            $strawberry
        ]);

        //Persist cake and clear out documentManager
        $documentManager->persist($cake);
        $documentManager->flush();
        $id = $cake->getId();
        $documentManager->clear();

        $cake = $documentManager->getRepository('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeWithSecrets')->findOneBy(['id' => $id]);

        Serializer::setMaxNestingDepth(1);
        $array = Serializer::toArray($cake, $documentManager);

        $this->assertCount(2, $array['ingredients']);
        $this->assertCount(1, $array['secretIngredients']);
        $this->assertEquals('strawberry', $array['secretIngredients'][0]['name']);


        $this->identity->addRole(Role::user);
        $filter->setRoles($this->identity->getRoles());

        $documentManager->clear();

        $cake = $documentManager->getRepository('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeWithSecrets')->findOneBy(['id' => $id]);

        Serializer::setMaxNestingDepth(1);
        $array = Serializer::toArray($cake, $documentManager);

        $this->assertCount(2, $array['ingredients']);
        $this->assertCount(2, $array['secretIngredients']);
        $this->assertEquals('chocolate', $array['secretIngredients'][0]['name']);
        $this->assertEquals('strawberry', $array['secretIngredients'][1]['name']);
    }
}