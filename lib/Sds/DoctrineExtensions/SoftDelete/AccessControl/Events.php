<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\SoftDelete\AccessControl;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
final class Events
{

    /**
     * Triggered when active identity attempts to soft delete a document they don't have permission
     * for
     */
    const softDeleteDenied = 'softDeleteDenied';

    /**
     * Triggers when active identity attempts to restore a document they don't have permission
     * for
     */
    const restoreDenied = 'restoreDenied';
}