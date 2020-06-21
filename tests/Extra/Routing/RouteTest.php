<?php

namespace Tests\Extra\Routing;

use Extra\Routing\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    /**
     * @dataProvider incorrectRoutesProvider
     */
    public function testIncorrectRoute($route)
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::get($route, 'action');
    }

    public function testGenerateUrl()
    {
        $route = Route::get('/blog/{section}/{id}', 'action');
        $url = $route->generateUrl(['section' => 'it', 'id' => 16]);

        $this->assertEquals('/blog/it/16', $url);
    }

    public function testGenerateUrlException()
    {
        $route = Route::get('/blog/{section}/{id}', 'action');

        $this->expectException(\InvalidArgumentException::class);
        $route->generateUrl(['section' => 'it']);
    }

    public function testGenerateUrlWithNotRequireArgument()
    {
        $route = Route::get('/posts/{section}/{?page}', 'action');

        $this->assertEquals('/posts/it/', $route->generateUrl(['section' => 'it']));
    }

    public function testGenerateUrlWithoutArgument()
    {
        $route = Route::get('/page', 'action');

        $this->assertEquals('/page', $route->generateUrl([]));
    }

    public function testExecuteCallback()
    {
        $route = Route::get('/news/{section}/{id}', function($section, $id){
            return ['section' => $section, 'id' => $id];
        })->rule('id', '[0-9]+');


        self::assertEquals(['section' => 'development', 'id' => 86], $route->execute('/news/development/86'));
    }

    public function testExecuteClass()
    {
        $route = Route::get('/test', '\Tests\Extra\Routing\ForTest@myMethod');

        self::assertEquals('myMethod', $route->execute('/test'));
    }

    public function testVariablesSubstitution()
    {
        $route = Route::get('/{folder}/{section}/{id}', function ($section, $id, $folder){
            return md5($section . $id . $folder);
        });

        self::assertEquals(md5('phones' . '6' . 'catalog'), $route->execute('/catalog/phones/6'));
    }

    public function testClassSubstitution()
    {
        $route = Route::get('/test', function (ForTest $forTest){
            return $forTest->myMethod();
        });

        self::assertEquals('myMethod', $route->execute('/test'));
    }

    //--------[helpers]--------

    public function incorrectRoutesProvider()
    {
        return [
            [' '],
            [''],
        ];
    }
}