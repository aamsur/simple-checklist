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
    
    $router->group(['prefix' => 'checklists/'], function () use ($router) {
        $router->post('templates/{template_id}/assigns', ['uses' => 'TemplateController@assign', 'as' => 'checklists.template.assign', 'where' => array('template_id' => '[0-9]+')]);
        $router->get('templates/{template_id}', ['uses' => 'TemplateController@show', 'as' => 'checklists.template.detail', 'where' => array('template_id' => '[0-9]+')]);
        $router->delete('templates/{template_id}', ['uses' => 'TemplateController@delete', 'as' => 'checklists.template.delete', 'where' => array('template_id' => '[0-9]+')]);
        $router->patch('templates/{template_id}', ['uses' => 'TemplateController@update', 'as' => 'checklists.template.update', 'where' => array('template_id' => '[0-9]+')]);
        
        $router->get('templates/', ['uses' => 'TemplateController@index', 'as' => 'checklists.template.list']);
        $router->post('templates/', ['uses' => 'TemplateController@create']);
        
        $router->post('complete/', 'ChecklistController@complete');
        $router->post('incomplete/', 'ChecklistController@incomplete');
        
        $router->get('{id}', ['uses' => 'ChecklistController@show', 'as' => 'checklists.detail', 'where' => array('id' => '[0-9]+')]);
        $router->delete('{id}', ['uses' => 'ChecklistController@delete', 'as' => 'checklists.delete', 'where' => array('id' => '[0-9]+')]);
        $router->patch('{id}', ['uses' => 'ChecklistController@update', 'as' => 'checklists.update', 'where' => array('id' => '[0-9]+')]);
        
        $router->get('/', 'ChecklistController@index');
        $router->post('/', 'ChecklistController@create');
    });
    
    $router->group(['prefix' => 'checklists/'], function () use ($router) {
        $router->get('{checklist_id}/items/{item_id}', ['uses' => 'ChecklistItemController@show', 'as' => 'checklists.item.detail', 'where' => array('item_id' => '[0-9]+', 'checklist_id' => '[0-9]+')]);
        $router->delete('{checklist_id}/items/{item_id}', ['uses' => 'ChecklistItemController@delete', 'as' => 'checklists.item.delete', 'where' => array('item_id' => '[0-9]+', 'checklist_id' => '[0-9]+')]);
        $router->patch('{checklist_id}/items/{item_id}', ['uses' => 'ChecklistItemController@update', 'as' => 'checklists.item.update', 'where' => array('item_id' => '[0-9]+', 'checklist_id' => '[0-9]+')]);
        
        $router->get('{checklist_id}/items/', ['uses' => 'ChecklistItemController@index', 'where' => array('checklist_id' => '[0-9]+')]);
        $router->post('{checklist_id}/items/', ['uses' => 'ChecklistItemController@create', 'where' => array('checklist_id' => '[0-9]+')]);
    });
    
});