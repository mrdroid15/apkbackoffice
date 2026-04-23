<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "404";
});
Route::get('api', [\App\Http\Controllers\ApiController::class, 'index']);