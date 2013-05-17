<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions;

use Zend\StdLib\AbstractOptions;

/**
 * A base class which extensions configs must extend
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
abstract class AbstractExtension extends AbstractOptions {

    protected $documents = [];

    protected $filters = [];

    protected $subscribers = [];

    protected $cliCommands = [];

    protected $cliHelpers = [];

    protected $serviceManagerConfig = [];

    /**
     * List of other extensions which must be loaded
     * for this extension to work
     *
     * @var array
     */
    protected $dependencies = [];

    public function getServiceManagerConfig() {
        return $this->serviceManagerConfig;
    }

    public function setServiceManagerConfig($serviceManagerConfig) {
        $this->serviceManagerConfig = $serviceManagerConfig;
    }

    public function getDocuments() {
        return $this->documents;
    }

    public function setDocuments($documents) {
        $this->documents = $documents;
    }

    public function getFilters() {
        return $this->filters;
    }

    public function setFilters($filters) {
        $this->filters = $filters;
    }

    public function getSubscribers() {
        return $this->subscribers;
    }

    public function setSubscribers($subscribers) {
        $this->subscribers = $subscribers;
    }

    public function getCliCommands() {
        return $this->cliCommands;
    }

    public function setCliCommands($cliCommands) {
        $this->cliCommands = $cliCommands;
    }

    public function getCliHelpers() {
        return $this->cliHelpers;
    }

    public function setCliHelpers($cliHelpers) {
        $this->cliHelpers = $cliHelpers;
    }

    /**
     *
     * @return array
     */
    public function getDependencies() {
        return $this->dependencies;
    }

    /**
     *
     * @param array $dependencies
     */
    public function setDependencies(array $dependencies) {
        $this->dependencies = $dependencies;
    }
}
