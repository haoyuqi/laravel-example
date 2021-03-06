<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'IndexController@index');
Route::get('/error', 'IndexController@error');
Route::get('/test', 'IndexController@test');
Route::get('/time', 'IndexController@time');

// queue test
Route::group(['prefix' => 'queue'], function ($route) {
    $route->get('/create', 'QueueController@create');
});

// sort
Route::group(['prefix' => 'sort'], function ($route) {
    $route->get('/bubble', 'SortController@bubbleSort');
    $route->get('/quick', 'SortController@quickSort');
    $route->get('/select', 'SortController@selectSort');
    $route->get('/insert', 'SortController@insertSort');
});
