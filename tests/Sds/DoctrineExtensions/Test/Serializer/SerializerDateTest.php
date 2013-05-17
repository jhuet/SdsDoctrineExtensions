<?php

namespace Sds\DoctrineExtensions\Test\Serializer;

use Sds\DoctrineExtensions\Manifest;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Birthday;

class SerializerDateTest extends BaseTest {

    public function setUp(){

        $manifest = new Manifest([
            'documents' => [
                __NAMESPACE__ . '\TestAsset\Document' => __DIR__ . '/TestAsset/Document'
            ],
            'extension_configs' => [
                'extension.serializer' => true
            ],
            'document_manager' => 'testing.documentmanager',
            'service_manager_config' => [
                'factories' => [
                    'testing.documentmanager' => 'Sds\DoctrineExtensions\Test\TestAsset\DocumentManagerFactory',
                ]
            ]
        ]);

        $this->documentManager = $manifest->getServiceManager()->get('testing.documentmanager');
        $this->serializer = $manifest->getServiceManager()->get('serializer');
    }

    public function testSerializerMongoDate(){

        $birthday = new Birthday('Miriam', new \MongoDate(strtotime('1950-01-01 Europe/Berlin')));

        $correct = [
            'name' => 'Miriam',
            'date' => '1949-12-31T23:00:00+00:00'
        ];

        $array = $this->serializer->toArray($birthday, $this->documentManager);

        $this->assertEquals($correct, $array);
    }

    public function testSerializerDateTime(){

        $birthday = new Birthday('Miriam', new \DateTime('01/01/1950', new \DateTimeZone('Europe/Berlin')));

        $correct = [
            'name' => 'Miriam',
            'date' => '1949-12-31T23:00:00+00:00'
        ];

        $array = $this->serializer->toArray($birthday, $this->documentManager);

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

        $array = $this->serializer->ApplySerializeMetadataToArray(
            $array,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Birthday'
        );

        $this->assertEquals($correct, $array);
    }

    public function testUnserializer(){

        $data = array(
            'name' => 'Miriam',
            'date' => '1949-12-31T23:00:00+00:00'
        );

        $birthday = $this->serializer->fromArray(
            $data,
            'Sds\DoctrineExtensions\Test\Serializer\TestAsset\Document\Birthday'
        );

        $this->assertTrue($birthday instanceof Birthday);
        $this->assertTrue($birthday->getDate() instanceof \DateTime);

        $birthday->getDate()->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $this->assertEquals('01/01/1950', $birthday->getDate()->format('d/m/Y'));
    }
}