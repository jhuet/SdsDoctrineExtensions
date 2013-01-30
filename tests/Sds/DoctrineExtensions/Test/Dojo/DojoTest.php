<?php

namespace Sds\DoctrineExtensions\Test\Dojo;

use Sds\DoctrineExtensions\Generator\Generator;
use Sds\DoctrineExtensions\Generator\GenerateEventArgs;
use Sds\DoctrineExtensions\Test\BaseTest;

class DojoTest extends BaseTest {

    protected $generator;

    protected $path;

    public function setUp(){
        parent::setUp();

        $this->path = __DIR__ . '/../../../../Dojo';

        $manifest = $this->getManifest(['Sds\DoctrineExtensions\Dojo' => [
            'destPaths' => [
                ['filter' => '', 'path' => $this->path]
            ]
        ]]);

        $this->configDoctrine(
            array_merge(
                $manifest->getDocuments(),
                array('Sds\DoctrineExtensions\Test\Dojo\TestAsset\Document' => __DIR__ . '/TestAsset/Document')
            ),
            $manifest->getFilters(),
            $manifest->getSubscribers()
        );
    }

    public function testInputGenerator(){

        $documentManager = $this->documentManager;
        $eventManager = $documentManager->getEventManager();

        $messages = new \ArrayObject();
        $eventManager->dispatchEvent(
            Generator::event,
            new GenerateEventArgs(
                $documentManager->getClassMetadata('Sds\DoctrineExtensions\Test\Dojo\TestAsset\Document\Simple'),
                $documentManager,
                $eventManager,
                $messages
           )
        );

        $this->assertEquals(
            file_get_contents(__DIR__ . '/TestAsset/Simple.js'),
            file_get_contents($this->path . '/Sds/DoctrineExtensions/Test/Dojo/TestAsset/Document/Simple.js')
        );
    }

    public function tearDown() {
    }
}