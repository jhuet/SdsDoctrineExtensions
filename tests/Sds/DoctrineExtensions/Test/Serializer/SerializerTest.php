<?php

namespace Sds\DoctrineExtensions\Test\Serializer;

use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Serializer\Serializer;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\User;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Group;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Profile;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\GetMethodError;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\HasDiscriminator;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\ClassName;

class SerializerTest extends BaseTest {

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

    public function testSerializer(){

        $user = new User();
        $user->setUsername('superdweebie');
        $user->setPassword('secret'); //uses Serialize Ignore annotation
        $user->defineLocation('here'); //uses serializeGetter annotation
        $user->addGroup(new Group('groupA'));
        $user->addGroup(new Group('groupB'));
        $user->setProfile(new Profile('Tim', 'Roediger'));

        $correct = array(
            'id' => null,
            'username' => 'superdweebie',
            'location' => 'here',
            'groups' => array(
                array('name' => 'groupA'),
                array('name' => 'groupB'),
            ),
            'profile' => array(
                'firstname' => 'Tim',
                'lastname' => 'Roediger'
            ),
        );

        $array = Serializer::toArray($user, $this->documentManager);

        $this->assertEquals($correct, $array);
    }

    public function testApplySerializeMetadataToArray(){

        $array = array(
            'id' => null,
            'username' => 'superdweebie',
            'location' => 'here',
            'groups' => array(
                array('name' => 'groupA'),
                array('name' => 'groupB'),
            ),
            'password' => 'secret',
            'profile' => array(
                'firstname' => 'Tim',
                'lastname' => 'Roediger'
            ),
        );

        $correct = array(
            'id' => null,
            'username' => 'superdweebie',
            'location' => 'here',
            'groups' => array(
                array('name' => 'groupA'),
                array('name' => 'groupB'),
            ),
            'profile' => array(
                'firstname' => 'Tim',
                'lastname' => 'Roediger'
            ),
        );

        $array = Serializer::ApplySerializeMetadataToArray(
            $array,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\User',
            $this->documentManager
        );

        $this->assertEquals($correct, $array);
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testSerializeGetterAnnotationError() {

        $errorDoc = new GetMethodError();
        $errorDoc->setName('error');
        Serializer::toArray($errorDoc, $this->documentManager);
    }

    public function testSerializeDiscriminator() {

        $testDoc = new HasDiscriminator();
        $testDoc->setName('superdweebie');

        $correct = array(
            'type' => 'hasDiscriminator',
            'id' => null,
            'name' => 'superdweebie',
        );

        $array = Serializer::toArray($testDoc, $this->documentManager);

        $this->assertEquals($correct, $array);
    }

    public function testSerializeClassName() {

        $testDoc = new ClassName();
        $testDoc->setName('superdweebie');

        $correct = array(
            '_className' => 'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\ClassName',
            'id' => null,
            'name' => 'superdweebie',
        );

        $array = Serializer::toArray($testDoc, $this->documentManager);

        $this->assertEquals($correct, $array);
    }

    public function testUnserializer(){

        $data = array(
            'username' => 'superdweebie',
            'location' => 'here',
            'groups' => array(
                array('name' => 'groupA'),
                array('name' => 'groupB'),
            ),
            'profile' => array(
                'firstname' => 'Tim',
                'lastname' => 'Roediger'
            ),
        );

        $user = Serializer::fromArray(
            $data,
            $this->documentManager,
            null,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\User'
        );

        $this->assertTrue($user instanceof User);
        $this->assertEquals('superdweebie', $user->getUsername());
        $this->assertEquals('here', $user->location());
        $this->assertEquals('groupA', $user->getGroups()[0]->getName());
        $this->assertEquals('groupB', $user->getGroups()[1]->getName());
        $this->assertEquals('Tim', $user->getProfile()->getFirstname());
        $this->assertEquals('Roediger', $user->getProfile()->getLastname());
    }

    public function testUnserializeClassName() {

        $data = array(
            '_className' => 'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\ClassName',
            'id' => null,
            'name' => 'superdweebie',
        );

        $testDoc = Serializer::fromArray($data, $this->documentManager);

        $this->assertTrue($testDoc instanceof ClassName);
        $this->assertEquals('superdweebie', $testDoc->getName());
    }
}