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
        | Future API Routes (extensible design)
        |--------------------------------------------------------------------------
        |
        | Add routes for blogs, products, etc. following the same pattern:
        |
        | Route::prefix('posts')->group(function () {
        |     Route::post('/', 'Ddk\Api\Controllers\Api\PostController@store');
        |     // ...
        | });
        |
        */
    });
});
