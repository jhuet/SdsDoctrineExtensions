<?php

namespace Sds\DoctrineExtensions\Test\Validator\TestAsset;

use Sds\Common\Validator\ValidatorInterface;
use Sds\Common\Validator\ValidatorResult;

class PropertyValidator1 implements ValidatorInterface {

    public function isValid($value) {
        $messages = [];

        if ($value == 'valid' || $value == 'alsoValid') {
            $result = true;
        } else {
            $messages[] = 'invalid name 1';
            $result = false;
        }

        return new ValidatorResult($result, $messages);
    }
}