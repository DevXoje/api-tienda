<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Here is where you can register
|
*/

Route::controller(AuthController::class)->group(function () {
	Route::apiResource('auth', AuthController::class);
	Route::post('login', 'login');
	Route::put('complete', 'complete');


	Route::post('logout', 'logout');
	Route::post('refresh', 'refresh');
	Route::post('me', 'me');
	Route::post('customers/remove/all', 'removeAllCustomers');
	Route::get('prices', 'getAllPrices');
	Route::get('restore', 'restore');
	Route::get('/redirect', 'redirect')->name('redirect');
});
