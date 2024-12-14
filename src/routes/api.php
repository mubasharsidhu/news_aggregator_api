<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserPreferencesController;
use App\Http\Controllers\ArticleController;

/**
 * user endpoints
 */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::middleware('auth:sanctum')->group(function () {

    // Get the current user's preferences
    Route::get('/user', [UserPreferencesController::class, 'user']);

    // Get the current user's preferences
    Route::get('/user/preferences', [UserPreferencesController::class, 'preferences']);

    // Update the current user's preferences
    Route::put('/user/preferences', [UserPreferencesController::class, 'update']);

    // Get personalized news feed based on user preferences
    Route::get('/articles/feeds/personalized', [ArticleController::class, 'articles'])->name('personalized.feed');

    // Get regular all feeds
    Route::get('/articles/feeds', [ArticleController::class, 'articles'])->name('general.feed');

    // Get pecific feed
    Route::get('/article/{id}', [ArticleController::class, 'article']);

    // Get Unique sources
    Route::get('/articles/unique-sources', [ArticleController::class, 'uniqueSources']);

    // Get Unique articles
    Route::get('/articles/unique-authors', [ArticleController::class, 'uniqueAuthors']);

});
