<?php

namespace Sds\DoctrineExtensions\Test\Crypt;

use Sds\DoctrineExtensions\Crypt\BlockCipherService;
use Sds\DoctrineExtensions\Test\BaseTest;
use Sds\DoctrineExtensions\Test\Crypt\TestAsset\Document\Simple;

class BlockCipherTest extends BaseTest {

    public function setUp(){

        parent::setUp();

        $this->configIdentity();

        $manifest = $this->getManifest(array('Sds\DoctrineExtensions\Crypt' => true));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array(__NAMESPACE__ . '\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
    }

    public function testBlockCipher(){
        $documentManager = $this->documentManager;

        $testDoc = new Simple();
        $testDoc->setName('Toby');

        $documentManager->persist($testDoc);
        $documentManager->flush();

        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = $repository->find($id);

        $this->assertNotEquals('Toby', $testDoc->getName());

        BlockCipherService::decryptDocument($testDoc, $documentManager->getClassMetadata(get_class($testDoc)));

        $this->assertEquals('Toby', $testDoc->getName());

        $testDoc->setName('Lucy');

        $documentManager->flush();
        $documentManager->clear();

        $testDoc = $repository->find($id);

        $this->assertNotEquals('Lucy', $testDoc->getName());

        BlockCipherService::decryptDocument($testDoc, $documentManager->getClassMetadata(get_class($testDoc)));

        $this->assertEquals('Lucy', $testDoc->getName());
    }
}