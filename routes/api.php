<?php

declare(strict_types=1);

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The signed-in user (Sanctum: session cookie for the SPA, or a token). Inactive accounts are refused.
Route::get('/user', fn (Request $request): UserResource => new UserResource($request->user()))
    ->middleware(['auth:sanctum', 'active', 'throttle:60,1']);
