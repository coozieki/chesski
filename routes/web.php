<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('home', [HomeController::class, 'home'])->name('home');

Route::group(['prefix'=>'game', 'as'=>'game.'], function() {
    Route::get('start', [HomeController::class, 'start']);
    Route::patch('move', [HomeController::class, 'move']);
});
