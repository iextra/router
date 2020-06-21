<?php

namespace Tests\Extra\Routing;

use Extra\Routing\Exceptions\RequestNotMatchedException;
use Extra\Routing\Router;
use Extra\Routing\Route;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testRoutesRegister()
    {
        $router = Router::getInstance();

        Route::get('/get', 'action');
        Route::post('/post', 'action');
        Route::put('/put', 'action');
        Route::delete('/delete', 'action');
        Route::some(['head', 'options', 'connect', 'trace', 'patch'],'/some', 'action');

        self::assertInstanceOf(Router::class, $router);
        return $router;
    }

    /**
     * @depends testRoutesRegister
     * @dataProvider routesProvide
     */
    public function testMatchRoute($route, $method, Router $router)
    {
        self::assertInstanceOf(Route::class, $router->match($route, $method));
    }

    public function testRouteNotMatchedException()
    {
        $router = Router::getInstance();

        $this->expectException(RequestNotMatchedException::class);
        $router->match('/nonexistent_path', 'GET');
    }

    // IncorrectAttributes
    public function testRouteNotMatchedException2()
    {
        $router = Router::getInstance();
        Route::get('/news/{id}', 'action')->rule('id', '[0-9]{3,6}');

        $this->expectException(RequestNotMatchedException::class);
        $router->match('/news/86', 'GET');
    }

    /**
     * @dataProvider requestMethodsProvide
     */
    public function testMatchRouteWithMethodAny($method)
    {
        $router = Router::getInstance();
        Route::any('/any', 'action');

        self::assertInstanceOf(Route::class, $router->match('/any', $method));
    }

    public function testGetRouteByName()
    {
        $router = Router::getInstance();
        Route::get('/articles', 'action')->name('articles');

        self::assertInstanceOf(Route::class, $router->getRouteByName('articles'));
    }

    public function testIncorrectRouteName()
    {
        $router = Router::getInstance();

        $this->expectException(\InvalidArgumentException::class);
        $router->getRouteByName('not_existing_name');
    }

    public function testRouteUrlGenerate()
    {
        $router = Router::getInstance();
        Route::get('/news/{section}/{id}', 'action')->name('news.detail');

        $url = $router->generateRouteUrl('news.detail', ['section' => 'it', 'id' => 16]);
        $this->assertEquals('/news/it/16', $url);
    }

    //--------[helpers]--------

    public function requestMethodsProvide()
    {
        return [
            ['GET'],
            ['HEAD'],
            ['POST'],
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['CONNECT'],
            ['TRACE'],
            ['PATCH'],
        ];
    }

    public function routesProvide()
    {
        return [
            ['/get', 'GET'],
            ['/some', 'HEAD'],
            ['/post', 'POST'],
            ['/put', 'PUT'],
            ['/delete', 'DELETE'],
            ['/some', 'OPTIONS'],
            ['/some', 'CONNECT'],
            ['/some', 'TRACE'],
            ['/some', 'PATCH'],
        ];
    }
}