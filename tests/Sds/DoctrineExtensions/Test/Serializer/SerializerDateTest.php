<?php

namespace Sds\DoctrineExtensions\Test\Serializer;

use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Serializer\Serializer;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Birthday;

class SerializerDateTest extends BaseTest {

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

    public function testSerializerMongoDate(){

        $birthday = new Birthday('Miriam', new \MongoDate(strtotime('1950-01-01 Europe/Berlin')));

        $correct = [
            'id' => null,
            'name' => 'Miriam',
            'date' => '1949-12-31T23:00:00+00:00'
        ];

        $array = Serializer::toArray($birthday, $this->documentManager);

        $this->assertEquals($correct, $array);
    }

    public function testSerializerDateTime(){

        $birthday = new Birthday('Miriam', new \DateTime('01/01/1950', new \DateTimeZone('Europe/Berlin')));

        $correct = [
            'id' => null,
            'name' => 'Miriam',
            'date' => '1949-12-31T23:00:00+00:00'
        ];

        $array = Serializer::toArray($birthday, $this->documentManager);

        $this->assertEquals($correct, $array);
    }

    public function testApplySerializeMetadataToArray(){

        $documentManager = $this->documentManager;
        $birthday = new Birthday('Miriam', new \DateTime('01/01/1950', new \DateTimeZone('Europe/Berlin')));
        $documentManager->persist($birthday);
        $documentManager->flush();
        $id = $birthday->getId();
        $documentManager->clear();

        $array = $documentManager
            ->createQueryBuilder()
            ->find('Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Birthday')
            ->field('id')->equals($id)
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $correct = [
            'id' => $id,
            'name' => 'Miriam',
            'date' => '1949-12-31T23:00:00+00:00'
        ];

        $array = Serializer::ApplySerializeMetadataToArray(
            $array,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Birthday',
            $this->documentManager
        );

        $this->assertEquals($correct, $array);
    }

    public function testUnserializer(){

        $data = array(
            'name' => 'Miriam',
            'date' => '1949-12-31T23:00:00+00:00'
        );

        $birthday = Serializer::fromArray(
            $data,
            $this->documentManager,
            null,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Birthday'
        );

        $this->assertTrue($birthday instanceof Birthday);
        $this->assertTrue($birthday->getDate() instanceof \DateTime);

        $birthday->getDate()->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $this->assertEquals('01/01/1950', $birthday->getDate()->format('d/m/Y'));
    }
}