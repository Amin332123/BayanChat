<?php

use App\Http\Controllers\ReflectionController;
use Illuminate\Support\Facades\Route;

Route::post('/process-reflection', [ReflectionController::class, 'handleInput']);
