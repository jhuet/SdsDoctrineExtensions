<?php

namespace Sds\DoctrineExtensions\Test\Serializer;

use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Serializer\Serializer;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEager;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEagerSimpleReference;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeRefLazy;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeSimpleLazySimpleReference;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Flavour;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\FlavourEager;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Ingredient;

class SerializerReferenceTest extends BaseTest {

    public function setUp(){

        parent::setUp();
        $manifest = $this->getManifest(array('Sds\DoctrineExtensions\Serializer' => true));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
    }

    public function testEagerSerializer(){

        $documentManager = $this->documentManager;

        //bake the eager cake. Hmm yum.
        $cake = new CakeEager();
        $cake->setIngredients([
            $this->createIngredient('flour'),
            $this->createIngredient('sugar'),
            $this->createIngredient('water'),
            $this->createIngredient('eggs')
        ]);

        $flavour = new FlavourEager('chocolate');
        $documentManager->persist($flavour);
        $cake->setFlavour($flavour);

        //Persist cake and clear out documentManager
        $documentManager->persist($cake);
        $documentManager->flush();
        $id = $cake->getId();
        $documentManager->clear();

        $cake = $documentManager->getRepository('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEager')->findOneBy(['id' => $id]);

        Serializer::setMaxNestingDepth(1);
        $array = Serializer::toArray($cake, $documentManager);

        $this->assertCount(4, $array['ingredients']);
        $this->assertEquals('flour', $array['ingredients'][0]['name']);
        $this->assertEquals('chocolate', $array['flavour']['name']);

        // maxNestingDepth = 1 should not display cakes
        $this->assertArrayNotHasKey('cakes', $array['flavour']);

        Serializer::setMaxNestingDepth(2);
        $array = Serializer::toArray($cake, $documentManager);

        // maxNestingDepth = 2 should display cakes
        $this->assertArrayHasKey('cakes', $array['flavour']);

        $array['ingredients'][3] = ['name' => 'coconut'];
        $cake = Serializer::fromArray($array, $documentManager);

        $this->assertInstanceOf('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEager', $cake);
        $this->assertEquals('chocolate', $cake->getFlavour()->getName());
        $this->assertCount(4, $cake->getIngredients());
        $this->assertEquals('coconut', $cake->getIngredients()[3]->getName());
    }

    public function testRefLazySerializer(){

        $documentManager = $this->documentManager;

        //bake the lazy cake. Hmm yum.
        $cake = new CakeRefLazy();
        $cake->setIngredients([
            $this->createIngredient('flour'),
            $this->createIngredient('sugar'),
            $this->createIngredient('water'),
            $this->createIngredient('eggs')
        ]);

        $flavour = new Flavour('carrot');
        $documentManager->persist($flavour);
        $cake->setFlavour($flavour);

        //Persist cake and clear out documentManager
        $documentManager->persist($cake);
        $documentManager->flush();
        $id = $cake->getId();
        $documentManager->clear();

        $cake = $documentManager->getRepository('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeRefLazy')->findOneBy(['id' => $id]);

        $array = Serializer::toArray($cake, $this->documentManager);

        $this->assertCount(4, $array['ingredients']);
        $this->assertArrayHasKey('$ref', $array['ingredients'][0]);
        $pieces = explode('/', $array['ingredients'][0]['$ref']);
        $this->assertCount(2, $pieces);
        $this->assertEquals('Ingredient', $pieces[0]);

        $this->assertArrayHasKey('$ref', $array['flavour']);
        $pieces = explode('/', $array['flavour']['$ref']);
        $this->assertCount(2, $pieces);
        $this->assertEquals('Flavour', $pieces[0]);


        $array['ingredients'][3] = ['name' => 'coconut'];
        $cake = Serializer::fromArray($array, $documentManager);

        $this->assertInstanceOf('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeRefLazy', $cake);
        $this->assertEquals('carrot', $cake->getFlavour()->getName());
        $this->assertCount(4, $cake->getIngredients());
        $this->assertEquals('water', $cake->getIngredients()[2]->getName());
        $this->assertEquals('coconut', $cake->getIngredients()[3]->getName());
    }

    public function testEagerApplyToArray(){

        $documentManager = $this->documentManager;

        //bake the cake. Hmm yum.
        $cake = new CakeEager();
        $cake->setIngredients([
            $this->createIngredient('flour'),
            $this->createIngredient('sugar'),
            $this->createIngredient('water'),
            $this->createIngredient('eggs')
        ]);

        $flavour = new FlavourEager('black_forest');
        $documentManager->persist($flavour);
        $cake->setFlavour($flavour);

        //Persist cake and clear out documentManager
        $documentManager->persist($cake);
        $documentManager->flush();
        $id = $cake->getId();
        $documentManager->clear();

        $cakeArray = $documentManager
            ->createQueryBuilder()
            ->find('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEager')
            ->field('id')->equals($id)
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $array = Serializer::applySerializeMetadataToArray(
            $cakeArray,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEager',
            $this->documentManager
        );

        $this->assertCount(4, $array['ingredients']);
        $this->assertEquals('flour', $array['ingredients'][0]['name']);
        $this->assertEquals('black_forest', $array['flavour']['name']);
    }

    public function testRefLazyApplyToArray(){

        $documentManager = $this->documentManager;

        //bake the cake. Hmm yum.
        $cake = new CakeRefLazy();
        $cake->setIngredients([
            $this->createIngredient('flour'),
            $this->createIngredient('sugar'),
            $this->createIngredient('water'),
            $this->createIngredient('eggs')
        ]);

        $flavour = new Flavour('carrot');
        $documentManager->persist($flavour);
        $cake->setFlavour($flavour);

        //Persist cake and clear out documentManager
        $documentManager->persist($cake);
        $documentManager->flush();
        $id = $cake->getId();
        $documentManager->clear();

        $cakeArray = $documentManager
            ->createQueryBuilder()
            ->find('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeRefLazy')
            ->field('id')->equals($id)
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $array = Serializer::applySerializeMetadataToArray(
            $cakeArray,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeRefLazy',
            $this->documentManager
        );

        $this->assertCount(4, $array['ingredients']);
        $this->assertArrayHasKey('$ref', $array['ingredients'][0]);
        $pieces = explode('/', $array['ingredients'][0]['$ref']);
        $this->assertCount(2, $pieces);
        $this->assertEquals('Ingredient', $pieces[0]);

        $this->assertArrayHasKey('$ref', $array['flavour']);
        $pieces = explode('/', $array['flavour']['$ref']);
        $this->assertCount(2, $pieces);
        $this->assertEquals('Flavour', $pieces[0]);
    }


    public function testEagerSerializerWithNull(){

        $documentManager = $this->documentManager;

        //bake the eager cake. Hmm yum.
        $cake = new CakeEager();

        //Persist cake and clear out documentManager
        $documentManager->persist($cake);
        $documentManager->flush();
        $id = $cake->getId();
        $documentManager->clear();

        $cake = $documentManager->getRepository('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEager')->findOneBy(['id' => $id]);

        $array = Serializer::toArray($cake, $documentManager);

        $this->assertArrayNotHasKey('ingredients', $array);
        $this->assertArrayNotHasKey('flavour', $array);

    }

    public function testEagerSerializerWithSimpleReference() {

        $documentManager = $this->documentManager;

        //bake the eager cake. Hmm yum.
        $cake = new CakeEagerSimpleReference();
        $cake->setIngredients([
            $this->createIngredient('flour'),
            $this->createIngredient('sugar'),
            $this->createIngredient('water'),
            $this->createIngredient('eggs')
        ]);

        $flavour = new FlavourEager('chocolate');
        $documentManager->persist($flavour);
        $cake->setFlavour($flavour);

        //Persist cake and clear out documentManager
        $documentManager->persist($cake);
        $documentManager->flush();
        $id = $cake->getId();
        $documentManager->clear();

        $cake = $documentManager->getRepository('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEagerSimpleReference')->findOneBy(['id' => $id]);

        $array = Serializer::toArray($cake, $documentManager);

        $this->assertCount(4, $array['ingredients']);
        $this->assertEquals('flour', $array['ingredients'][0]['name']);
        $this->assertEquals('chocolate', $array['flavour']['name']);

        $array['ingredients'][3] = ['name' => 'coconut'];
        $cake = Serializer::fromArray($array, $documentManager);

        $this->assertInstanceOf('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeEagerSimpleReference', $cake);
        $this->assertEquals('chocolate', $cake->getFlavour()->getName());
        $this->assertCount(4, $cake->getIngredients());
        $this->assertEquals('coconut', $cake->getIngredients()[3]->getName());
    }

    public function testSimpleLazySerializerWithSimpleReference() {

        $documentManager = $this->documentManager;

        //bake the cake. Hmm yum.
        $cake = new CakeSimpleLazySimpleReference();
        $cake->setIngredients([
            $this->createIngredient('flour'),
            $this->createIngredient('sugar'),
            $this->createIngredient('water'),
            $this->createIngredient('eggs')
        ]);

        $flavour = new Flavour('carrot');
        $documentManager->persist($flavour);
        $cake->setFlavour($flavour);

        //Persist cake and clear out documentManager
        $documentManager->persist($cake);
        $documentManager->flush();
        $id = $cake->getId();
        $documentManager->clear();

        $cakeArray = $documentManager
            ->createQueryBuilder()
            ->find('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeSimpleLazySimpleReference')
            ->field('id')->equals($id)
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $array = Serializer::applySerializeMetadataToArray(
            $cakeArray,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\CakeSimpleLazySimpleReference',
            $this->documentManager
        );

        $this->assertCount(4, $array['ingredients']);
        $pieces = explode('/', $array['ingredients'][0]);
        $this->assertCount(2, $pieces);
        $this->assertEquals('Ingredient', $pieces[0]);

        $pieces = explode('/', $array['flavour']);
        $this->assertCount(2, $pieces);
        $this->assertEquals('Flavour', $pieces[0]);
    }

    protected function createIngredient($name){
        $ingredient = new Ingredient($name);
        $this->documentManager->persist($ingredient);
        return $ingredient;
    }
}