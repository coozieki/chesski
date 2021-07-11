<?php

use App\Http\Controllers\GameController;
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

Route::get('chess/{id?}', [GameController::class, 'chess'])->name('chess');

Route::group(['prefix'=>'game', 'as'=>'game.'], function() {
    Route::get('start', [GameController::class, 'start']);
    Route::patch('move', [GameController::class, 'move']);
    Route::get('moves', [GameController::class, 'getMoves']);
});
