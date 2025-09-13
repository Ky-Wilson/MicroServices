<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WikiController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/ask', [WikiController::class, 'index'])->name('ask.form');
Route::post('/ask', [WikiController::class, 'ask'])->name('ask');