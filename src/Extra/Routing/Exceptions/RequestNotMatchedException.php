<?php

namespace Extra\Routing\Exceptions;

use Exception;

class RequestNotMatchedException extends Exception
{
    private $requestUti;
    private $requestMethod;

    function __construct($message = '', $requestUti, $requestMethod)
    {
        parent::__construct($message);

        $this->requestUti = $requestUti;
        $this->requestMethod = $requestMethod;
    }

    public function getRequestUri(): string
    {
        return $this->requestUti;
    }

    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }
}