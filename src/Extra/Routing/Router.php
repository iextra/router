<?php

namespace Extra\Routing;

use Extra\Routing\Exceptions\RequestNotMatchedException;

class Router extends Singleton
{
    private $routeCollection = [];

    public function match(string $requestUri, string $requestMethod): Route
    {
        foreach($this->routeCollection as $route){
            if($route->isAvailableMethod($requestMethod) === true){
                $routePattern = $route->getPattern();

                if(preg_match('~^' . $routePattern . '$~ui', $requestUri) === 1){
                    return $route;
                }
            }
        }

        throw new RequestNotMatchedException('Request not matched', $requestUri, $requestMethod);
    }

    public function getRouteByName(string $routeName): Route
    {
        foreach($this->routeCollection as $route){
            if($route->getName() === $routeName){
                return $route;
            }
        }

        throw new \InvalidArgumentException('Route [' . $routeName . '] not defined');
    }

    public function registerRoute(Route $route): void
    {
        $this->routeCollection[$route->getId()] = $route;
    }

    public function includeRoutesFile(string $routeFile): self
    {
        require_once $routeFile;
        return $this;
    }

    public function generateRouteUrl(string $routeName, array $params = []): string
    {
        $route = $this->getRouteByName($routeName);
        return $route->generateUrl($params);
    }
}