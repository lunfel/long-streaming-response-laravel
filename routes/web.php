<?php

use App\Http\Controllers\ExplorationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/exploration', ExplorationController::class);
