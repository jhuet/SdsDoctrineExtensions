<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Dojo\Generator;

use Sds\DoctrineExtensions\Generator\GeneratorResult as BaseGeneratorResult;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GeneratorResult extends BaseGeneratorResult {

    protected $mid;

    public function getMid() {
        return $this->mid;
    }

    public function setMid($mid) {
        $this->mid = $mid;
    }
}