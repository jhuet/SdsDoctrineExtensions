<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\AccessControl;

use Sds\DoctrineExtensions\AbstractExtensionConfig;
use Sds\DoctrineExtensions\RolesExtensionConfigTrait;

/**
 * Defines the resouces this extension requires
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class ExtensionConfig extends AbstractExtensionConfig
{

    use RolesExtensionConfigTrait;

    /**
     * Defines the permission class to use
     *
     * @var boolean
     */
    protected $permissionClass = 'Sds\DoctrineExtensions\AccessControl\DataModel\Permission';

    /**
     *
     * @var array
     */
    protected $dependencies = array(
        'Sds\DoctrineExtensions\Annotation' => true,
        'Sds\DoctrineExtensions\State' => true,
    );

    /**
     *
     * @return string
     */
    public function getPermissionClass() {
        return $this->permissionClass;
    }

    /**
     *
     * @param string $permissonClass
     */
    public function setPermissionClass($permissionClass) {
        $this->permissionClass = (string) $permissionClass;
    }
}
