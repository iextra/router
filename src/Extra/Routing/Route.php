<?php

namespace Extra\Routing;

class Route
{
    private $availableMethods = [];
    private $route = null;
    private $id = null;
    private $name = null;
    private $routePattern = null;
    private $action = null;
    private $arRules = [];

    public static function get(string $route, $action): self
    {
        return self::init(['GET'], $route, $action);
    }

    public static function post(string $route, $action): self
    {
        return self::init(['POST'], $route, $action);
    }

    public static function put(string $route, $action): self
    {
        return self::init(['PUT'], $route, $action);
    }

    public static function delete(string $route, $action): self
    {
        return self::init(['DELETE'], $route, $action);
    }

    public static function any(string $route, $action): self
    {
        return self::init([
            'GET',
            'HEAD',
            'POST',
            'PUT',
            'DELETE',
            'OPTIONS',
            'TRACE',
            'CONNECT',
            'PATCH'
        ], $route, $action);
    }

    public static function some(array $methods, string $route, $action): self
    {
        foreach($methods as $key => $method){
            $data[] = strtoupper($method);
        }
        return self::init($data, $route, $action);
    }

    public function rules(array $rules): self
    {
        foreach ($rules as $marker => $pattern) {
            $this->arRules[$marker] = $pattern;
        }
        return $this;
    }

    public function rule(string $marker, string $pattern): self
    {
        return $this->rules([$marker => $pattern]);
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPattern(): string
    {
        if($this->routePattern === null){
            $this->routePattern = $this->makePattern();
        }

        return $this->routePattern;
    }

    public function generateUrl(array $arguments = []): string
    {
        $url = preg_replace_callback('~{(.*?)}~ui', function ($matches) use ($arguments) {
           $argument = $matches[1];

           if(array_key_exists($argument, $arguments) === false){
               $argumentIsRequire = (strpos($argument, '?') === false) ? true : false;
               if($argumentIsRequire === true){
                   throw new \InvalidArgumentException("Missing required parameter [{$argument}]");
               }
               else {
                   $arguments[$argument] = '';
               }
           }

           return $arguments[$argument];
        }, $this->route);

        return $url;
    }

    /**
     * @return string | null
     */
    public function getName()
    {
        return $this->name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isAvailableMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->availableMethods);
    }

    public function execute(string $requestUri)
    {
        $action = $this->action;
        $arArgs = $this->parseUrlArgs($requestUri);

        if(is_string($action) === true){
            if(strpos($action, '@') !== false){
                $arr = explode('@', $action);
                $className = $arr[0];
                $methodName = $arr[1];
                return $this->callClass($className, $methodName, $arArgs);
            }
            else{
                $functionName = $action;
                return $this->callFunction($functionName, $arArgs);
            }
        }
        else if(is_callable($action) === true){
            $callBack = $action;
            return $this->callCallback($callBack, $arArgs);
        }

        throw new \InvalidArgumentException("Action for route [{$this->route}] is not valid");
    }


    private static function init(array $methods, string $route, $action): self
    {
        if(empty(trim($route))){
            throw new \InvalidArgumentException("The route can't be empty");
        }

        $self = new self();

        $self->availableMethods = $methods;
        $self->route = $route;
        $self->action = $action;
        $self->id = md5($self->route . implode('', $self->availableMethods));

        Router::getInstance()->registerRoute($self);

        return $self;
    }

    private function makePattern(): string
    {
        preg_match_all('~{(.*?)}~ui', $this->route, $matches);

        if(!empty($matches[1])){
            foreach ($matches[1] as $arg){
                $notRequire = (strpos($arg, '?') !== false) ? '?' : '';
                $argName = str_replace('?', '', $arg);
                $rule = !empty($this->arRules[$argName]) ? $this->arRules[$argName] : '[\w]+';
                $this->arRules[$argName] = $rule;

                $argPatterns[] = '~{' . $argName . '}~';
                $argReplacement[] = '(?P<' . $argName . '>' . $rule . ')' . $notRequire;
            }

            $route = str_replace('?', '', $this->route);
            $result = preg_replace($argPatterns, $argReplacement, $route);
        }
        else{
            $result = $this->route;
        }

        return $result;
    }

    //[execute helpers]--------------------------------------------------------------------

    private function parseUrlArgs(string $requestUri): array
    {
        $result = [];
        $routePattern = $this->getPattern();

        preg_match('~^' . $routePattern . '$~ui', $requestUri, $matches);
        if(!empty($matches)){
            foreach ($matches as $key => $value){
                if(!empty($this->arRules[$key])){
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    private function callFunction(string $functionName, array $args = [])
    {
        if(function_exists($functionName) === true){
            $args = $this->prepareFuncArgs($functionName, $args);
            return call_user_func_array($functionName, $args);
        }

        throw new \InvalidArgumentException("Function [{$functionName}] not found");
    }

    private function callCallback(callable $callBack, array $args = [])
    {
        $args = $this->prepareFuncArgs($callBack, $args);
        return call_user_func_array($callBack, $args);
    }

    private function callClass(string $className, string $methodName, array $args = [])
    {
        if(class_exists($className) === true){
            $object = new $className;

            if(method_exists($object, $methodName) === true){
                $args = $this->prepareMethodArgs($className, $methodName, $args);
                return call_user_func_array([$object, $methodName], $args);
            }

            throw new \InvalidArgumentException("Method [{$methodName}] in class [{$className}] not found");
        }

        throw new \InvalidArgumentException("Class [{$className}] not found");
    }

    private function prepareMethodArgs(string $className, string $methodName, array $data): array
    {
        $reflection = new \ReflectionMethod($className, $methodName);
        return $this->prepareArgs($reflection, $data);
    }

    private function prepareFuncArgs($funcName, array $data): array
    {
        $reflection = new \ReflectionFunction($funcName);
        return $this->prepareArgs($reflection, $data);
    }

    private function prepareArgs(\Reflector $reflection, array $data): array
    {
        $result = [];

        foreach ($reflection->getParameters() as $arg){
            $argName = $arg->name;
            $position = $arg->getPosition();

            $class = $arg->getClass();
            if($class !== null){
                $data[$argName] = new $class->name;
            }

            if(isset($data[$argName])){
                $result[$position] = $data[$argName];
            }
            else if($arg->isDefaultValueAvailable() === true){
                $result[$position] = $arg->getDefaultValue();
            }
        }

        return $result;
    }
}