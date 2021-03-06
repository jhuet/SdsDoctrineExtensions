<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Freeze\AccessControl;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
final class Events
{

    /**
     * Triggered when active identity attempts to freeze a document they don't have permission
     * for
     */
    const freezeDenied = 'freezeDenied';

    /**
     * Triggers when active identity attempts to thaw a document they don't have permission
     * for
     */
    const thawDenied = 'thawDenied';
}