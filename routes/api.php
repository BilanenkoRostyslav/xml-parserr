<?php

use App\Http\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/catalog/products', [MainController::class, 'offers']);
Route::get('/catalog/filters', [MainController::class, 'filters']);
