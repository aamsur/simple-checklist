<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->version();
});

$router->group(['prefix' => 'api/v1/'], function () use ($router) {
    $router->get('login/', 'UsersController@authenticate');
    $router->post('todo/', 'TodoController@store');
    $router->get('todo/', 'TodoController@index');
    $router->get('todo/{id}/', 'TodoController@show');
    $router->put('todo/{id}/', 'TodoController@update');
    $router->delete('todo/{id}/', 'TodoController@destroy');
    
    $router->group(['prefix' => 'checklists/'], function () use ($router) {
        $router->get('', 'ChecklistController@index');
        $router->get('{id}', ['uses' => 'ChecklistController@show', 'as' => 'checklists.detail', 'where' => array('id' => '[0-9]+')]);
        $router->post('', 'ChecklistController@create');
        $router->delete('{id}', ['uses' => 'ChecklistController@delete', 'as' => 'checklists.delete', 'where' => array('id' => '[0-9]+')]);
        $router->patch('{id}', ['uses' => 'ChecklistController@update', 'as' => 'checklists.update', 'where' => array('id' => '[0-9]+')]);
    });
});