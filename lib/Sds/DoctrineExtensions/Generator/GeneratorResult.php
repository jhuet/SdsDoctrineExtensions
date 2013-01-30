<?php
/**
 * @link       http://superdweebie.com
 * @package    Sds
 * @license    MIT
 */
namespace Sds\DoctrineExtensions\Generator;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GeneratorResult {

    protected $message;

    protected $fileGenerated;

    public function __construct(array $args = []){
        foreach ($args as $key => $value){
            $this->$key = $value;
        }
    }

    public function getMessage() {
        return $this->message;
    }

    public function getFileGenerated() {
        return $this->fileGenerated;
    }
}