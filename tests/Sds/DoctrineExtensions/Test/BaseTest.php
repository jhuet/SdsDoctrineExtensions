<?php

namespace Sds\DoctrineExtensions\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Sds\DoctrineExtensions\Manifest;
use Sds\DoctrineExtensions\ManifestConfig;
use Sds\DoctrineExtensions\Test\TestAsset\RoleAwareIdentity;
use Sds\DoctrineExtensions\Test\TestAsset\Identity;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{

    const DEFAULT_DB = 'sds_doctrine_extensions_tests';

    protected $documentManager;

    protected $unitOfWork;

    protected $annotationReader;

    protected $identity;

    public function setUp(){
        $this->annotationReader = new AnnotationReader();
    }

    protected function configIdentity($configRoleAwareIdentity = false){
        $identity = $configRoleAwareIdentity ? new RoleAwareIdentity() : new Identity();
        $identity->setIdentityName('toby');
        $this->identity = $identity;
    }

    protected function getManifest(array $extensionConfigs){

        $config = array(
            'annotationReader' => $this->annotationReader,
            'extensionConfigs' => $extensionConfigs
        );
        if (isset($this->identity)) {
            $config['identity'] = $this->identity;
        }
        $manifestConfig = new ManifestConfig($config);

        return new Manifest($manifestConfig);
    }

    protected function configDoctrine(
        array $documents = array(),
        array $filters = array(),
        array $subscribers = array()
    ){

        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../Proxies');
        $config->setProxyNamespace('Proxies');

        $config->setHydratorDir(__DIR__ . '/../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');

        $config->setDefaultDB(self::DEFAULT_DB);

        //create driver chain
        $chain  = new MappingDriverChain;

        foreach ($documents as $namespace => $path){
            $driver = new AnnotationDriver($this->annotationReader, $path);
            $chain->addDriver($driver, $namespace);
        }
        $config->setMetadataDriverImpl($chain);

        //register filters
        foreach ($filters as $name => $class){
            $config->addFilter($name, $class);
        }

        //create event manager
        $eventManager = new EventManager();
        foreach($subscribers as $subscriber){
            $eventManager->addEventSubscriber($subscriber);
        }

        //register annotations
        AnnotationRegistry::registerLoader(function($className) {
            return class_exists($className);
        });

        $conn = new Connection(null, array(), $config);
        $this->documentManager = DocumentManager::create($conn, $config, $eventManager);
        $this->unitOfWork = $this->documentManager->getUnitOfWork();
    }

    public function tearDown()
    {
        if ($this->documentManager) {
            $collections = $this->documentManager->getConnection()->selectDatabase(self::DEFAULT_DB)->listCollections();
            foreach ($collections as $collection) {
                $collection->remove(array(), array('safe' => true));
            }
        }
    }
}