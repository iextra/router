# `Extra\Routing`


## Adding route
> Route takes 2 parameters. <br>
> The first is the URL pattern. The second is the 'action' (to be performed)
```php
use Extra\Routing\Route;

Route::get('/', function(){
    return 'Hello World';
});
````

## Available methods for adding routes
```php
use Extra\Routing\Route;

Route::any('/', 'action');
Route::get('/news', 'action');
Route::post('/news/{id}', 'action');
Route::put('/news/{id}', 'action');
Route::delete('/news/{id}', 'action');
Route::some(['head', 'options', 'connect', 'trace', 'patch'], '/test', 'action');
````
## Available 'actions' for route
```php
use Extra\Routing\Route;

// Function name
Route::get('/', 'FunctionName');

// The class name and method name are specified through the @ separator
Route::get('/', 'ClassName@method');

// Callback function
Route::get('/', function(){
    return 'Hello World';
});
````

## Search and execute matching route
```php
use Extra\Routing\Router;

try {
    $router = Router::getInstance()->includeRoutesFile('path_to_file_with_routes.php');

    $route = $router->match($requestUri, $requestMethod);
    $result = $route->execute($requestUri);
}
catch (\Exception $e){
   die($e->getMessage());
}
```

## Execute route
> In order for the parameter to be passed to the function, <br>
> it is necessary that the name of the variable that the function accepts <br>
>  has the same name as indicated in the URL {id}
```php
use Extra\Routing\Router; 

$route = Route::get('/news/{id}', function($id){
    return 'Id of the news: ' . $id;
});

$requestUrl = '/post/86';
$result = $route->execute($requestUrl);

echo $result; // 'Id of post: 86'
```

## Setting route name
```php
use Extra\Routing\Route;

Route::get('/news/', 'NewsController@list')->name('news.list');
Route::get('/news/{id}', 'NewsController@detail')->name('news.detail');
````

## Setting route rules
```php
use Extra\Routing\Route;

// One rule
Route::delete('/news/{id}', 'NewsController@delete')->rule('id', '[0-9]+');

// Few rules
Route::post('/catalog/{section}/{id}', 'CatalogController@detail')->rules([
    'section' => '(cars|motorbikes)',
    'id' => '\d+'
]);

// To indicate that the parameter is optional, 
// you need to put a question mark in front of it {?page}
Route::get('/news/{section}/{?page}', function($section, $page = 1){
    return 'Section: ' . $section . ' | Page: ' . $page;
});
````

## Search route by name
```php
use Extra\Routing\Router;

$router = Router::getInstance();
$route = $router->getRouteByName('news.list');
```

## Generate route URL
```php
use Extra\Routing\Router;
use Extra\Routing\Route;

$router = Router::getInstance();
Route::get('/news/{id}', 'action')->name('news.detail');

// Way 1 (using router)
$url = $router->generateRouteUrl('news.detail', ['id' => 86]); 

// Way 2 (using route)
$route = $router->getRouteByName('news.detail');
$url = $route->generateUrl(['id' => 86]); 

echo $url; // '/news/86';
```