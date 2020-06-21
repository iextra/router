<?php

namespace Extra\Routing;

abstract class Singleton
{
    final public static function getInstance()
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new static();
        }

        return $instance;
    }

    final protected function __clone() {}

    final protected function __construct() {}
}