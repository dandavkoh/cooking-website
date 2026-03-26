<?php

use Illuminate\Support\Facades\Route;
use Ddk\Api\Http\Middleware\ApiAuthentication;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api/v1 and require authentication.
| The API uses Bearer token authentication.
|
*/

Event::listen('system.beforeRoute', function () {
    Route::group([
        'prefix' => 'api/v1',
        'middleware' => ['web', ApiAuthentication::class],
    ], function () {

        /*
        |--------------------------------------------------------------------------
        | Recipe API Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('recipes')->group(function () {
            Route::post('/', 'Ddk\Api\Controllers\Api\RecipeController@store');
            Route::put('/{id}', 'Ddk\Api\Controllers\Api\RecipeController@update');
            Route::delete('/{id}', 'Ddk\Api\Controllers\Api\RecipeController@destroy');
            Route::get('/', 'Ddk\Api\Controllers\Api\RecipeController@index');
            Route::get('/{id}', 'Ddk\Api\Controllers\Api\RecipeController@show');
        });

        /*
        |--------------------------------------------------------------------------
        | Blog Post API Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('posts')->group(function () {
            Route::get('/', 'Ddk\Api\Controllers\Api\BlogPostController@index');
            Route::post('/', 'Ddk\Api\Controllers\Api\BlogPostController@store');
            Route::get('/{id}', 'Ddk\Api\Controllers\Api\BlogPostController@show');
            Route::put('/{id}', 'Ddk\Api\Controllers\Api\BlogPostController@update');
            Route::delete('/{id}', 'Ddk\Api\Controllers\Api\BlogPostController@destroy');
        });
    });
});
