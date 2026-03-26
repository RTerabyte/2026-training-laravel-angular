<?php

use App\User\Infrastructure\Entrypoint\Http\PostController;
use Illuminate\Support\Facades\Route;
use App\Family\Infrastructure\Entrypoint\Http\PostController as FamilyPostController;

Route::post('/users', PostController::class);
Route::post('', FamilyPostController::class);