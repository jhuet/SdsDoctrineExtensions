<?php

namespace Sds\DoctrineExtensions\Test\Identity;

use Sds\Common\Crypt\Hash;
use Sds\DoctrineExtensions\Test\Identity\TestAsset\Document\CredentialTraitDoc;
use Sds\DoctrineExtensions\Test\BaseTest;

class CredentialTraitTest extends BaseTest {

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

    public function testPassword(){

        $password = 'password1';
        $doc = new CredentialTraitDoc();

        $doc->setCredential($password);

        $this->documentManager->persist($doc);
        $this->documentManager->flush();

        $this->assertNotEquals($password, $doc->getCredential());
        $this->assertEquals($doc->getCredential(), Hash::hashCredential($doc, $password));
        $this->assertNotEquals($doc->getCredential(), Hash::hashCredential($doc, 'not password'));

        $newPassword = 'new password';
        $doc->setCredential($newPassword);

        $this->documentManager->flush();

        $this->assertNotEquals($newPassword, $doc->getCredential());
        $this->assertEquals($doc->getCredential(), Hash::hashCredential($doc, $newPassword));
        $this->assertNotEquals($doc->getCredential(), Hash::hashCredential($doc, $password));
    }
}