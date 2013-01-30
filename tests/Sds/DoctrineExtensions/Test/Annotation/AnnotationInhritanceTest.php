<?php

namespace Sds\DoctrineExtensions\Test\Annotation;

use Sds\DoctrineExtensions\Annotation\Annotations as Sds;
use Sds\DoctrineExtensions\Test\Annotation\TestAsset\Document\ChildA;
use Sds\DoctrineExtensions\Test\Annotation\TestAsset\Document\ChildB;
use Sds\DoctrineExtensions\Test\BaseTest;

class AnnotationInheritaceTest extends BaseTest {

    public function setUp(){
        parent::setUp();

        $this->configIdentity(true);

        $manifest = $this->getManifest(array(
            'Sds\DoctrineExtensions\DoNotHardDelete' => true,
            'Sds\DoctrineExtensions\Dojo' => array(
                'destPaths' => array(
                    'filter' => '',
                    'path' => __DIR__ . '/../../../../Dojo'
            )),
            'Sds\DoctrineExtensions\Serializer' => true,
            'Sds\DoctrineExtensions\Validator' => true,
            'Sds\DoctrineExtensions\Workflow' => true
        ));

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\Annotation\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
    }

    public function testAnnotationInheritance(){

        $documentManager = $this->documentManager;

        $metadata = $documentManager->getClassMetadata(get_class(new ChildA));

        $this->assertTrue($metadata->doNotHardDelete);
        $this->assertTrue($metadata->serializer['className']);
        $this->assertEquals('_className', $metadata->serializer['classNameProperty']);
        $this->assertTrue($metadata->serializer['discriminator']);
        $this->assertEquals([['class' => 'ParentValidator', 'options' => []]], $metadata->validator['document']);
        $this->assertEquals('ParentWorkflow', $metadata->workflow);
        $this->assertEquals('ignore_always', $metadata->serializer['fields']['name']['ignore']);
    }

    public function testAnnotationInheritanceOverride(){

        $documentManager = $this->documentManager;

        $metadata = $documentManager->getClassMetadata(get_class(new ChildB));

        $this->assertFalse($metadata->doNotHardDelete);
        $this->assertFalse($metadata->serializer['className']);
        $this->assertFalse($metadata->serializer['discriminator']);
        $this->assertEquals([['class' => 'ChildBValidator', 'options' => []]], $metadata->validator['document']);
        $this->assertEquals('ChildBWorkflow', $metadata->workflow);
        $this->assertEquals('none', $metadata->serializer['fields']['name']['ignore']);
    }
}