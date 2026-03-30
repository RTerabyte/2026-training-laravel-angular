<?php

use App\User\Infrastructure\Entrypoint\Http\PostController;
use Illuminate\Support\Facades\Route;
use App\User\Infrastructure\Entrypoint\Http\LoginPostController;

use App\Family\Infrastructure\Entrypoint\Http\ActivateController as FamilyActivateController;
use App\Family\Infrastructure\Entrypoint\Http\DeactivateController as FamilyDeactivateController;
use App\Family\Infrastructure\Entrypoint\Http\DeleteController as FamilyDeleteController;
use App\Family\Infrastructure\Entrypoint\Http\GetController as FamilyGetController;
use App\Family\Infrastructure\Entrypoint\Http\IndexController as FamilyIndexController;
use App\Family\Infrastructure\Entrypoint\Http\PostController as FamilyPostController;
use App\Family\Infrastructure\Entrypoint\Http\PutController as FamilyPutController;

Route::post('/users', PostController::class);
Route::post('/login', LoginPostController::class);

Route::get('/families', FamilyIndexController::class);
Route::post('/families', FamilyPostController::class);
Route::get('/families/{id}', FamilyGetController::class);
Route::put('/families/{id}', FamilyPutController::class);
Route::patch('/families/{id}/activate', FamilyActivateController::class);
Route::patch('/families/{id}/deactivate', FamilyDeactivateController::class);
Route::delete('/families/{id}', FamilyDeleteController::class);
