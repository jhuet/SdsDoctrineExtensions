<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions;

use Sds\DoctrineExtensions\Exception;

/**
 * A base class which extensions may extend
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
abstract class AbstractExtension implements ExtensionInterface {

    /**
     *
     * @var string
     */
    protected $configClass;

    /**
     *
     * @var \SdsDoctrineExtensions\AbstractExtensionConfig
     */
    protected $config;

    /**
     *
     * @var array
     */
    protected $filters = array();

    /**
     *
     * @var array
     */
    protected $subscribers = array();

    /**
     *
     * @var array
     */
    protected $documents = array();

    /**
     *
     * @var array
     */
    protected $cliCommands = array();

    /**
     *
     * @var array
     */
    protected $cliHelpers = array();

    /**
     *
     * @param \SdsDoctrineExtensions\AbstractExtensionConfig $config
     */
    public function __construct($config = null){
        $configClass = $this->configClass;

        if (is_array($config) ||
            ($config instanceof \Traversable)
        ) {
            $config = new $configClass($config);
        } elseif (!($config instanceof $configClass) && isset($config)) {
            throw new Exception\InvalidArgumentException(sprintf('Argument supplied to Extension constructor must be array, implement Traversable, or instance of %s',
                $configClass));
        }
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(){
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(){
        return $this->filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribers(){
        return $this->subscribers;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocuments(){
        return $this->documents;
    }

    /**
     * {@inheritdoc}
     */
    public function getCliCommands(){
        return $this->cliCommands;
    }

    /**
     * {@inheritdoc}
     */
    public function getCliHelpers(){
        return $this->cliHelpers;
    }

    public function setIdentity($identity){
        $this->config->setIdentity($identity);
    }
}
