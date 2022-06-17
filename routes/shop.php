<?php

use App\Http\Controllers\LineItemsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Here is where you can register
|
*/


/*
Route::group(['prefix' => 'auth.payments'], function () {
	Route::apiResources([
		'/' => PaymentController::class,
		'/{payments}/line-items' => LineItemsController::class,
	]);
});
*/
Route::prefix('auth.payments')->controller(PaymentController::class)->group(function () {
	Route::apiResources([
		'/' => PaymentController::class,
		'/{payments}/line-items' => LineItemsController::class,
	]);

});


Route::prefix('checkout')->controller(PaymentController::class)->group(function () {
	//Route::apiResource("", PaymentController::class);
	Route::post('/', 'createCheckoutSession');
	Route::post('asociate', 'acociactePaymentMethodToUser');
	Route::post('success', 'success')->name('checkout.success');
	Route::post('cancel', 'cancel')->name('checkout.cancel');


	Route::post("payments", "addPayment");
	Route::post("checkout", "checkout");

});
Route::prefix('products')->controller(ProductsController::class)->group(function () {
	Route::apiResource("", ProductsController::class);
	Route::post("removeAll", "removeAllProducts");

});
/*

Route::post('products/remove/all', [ProductsController::class, 'removeAllProducts']);
Route::post('test', [PaymentController::class, 'test']);*/
