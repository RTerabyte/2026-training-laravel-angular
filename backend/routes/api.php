<?php

use App\User\Infrastructure\Entrypoint\Http\PostController;
use Illuminate\Support\Facades\Route;
use App\User\Infrastructure\Entrypoint\Http\LoginPostController;

Route::post('/users', PostController::class);
Route::post('/login', LoginPostController::class);
