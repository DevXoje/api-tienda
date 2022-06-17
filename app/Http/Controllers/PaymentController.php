<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class PaymentController extends ApiController
{
	public function __construct()
	{
		$this->middleware('auth:api');
	}

	public function index()
	{
		if (!$payments = Payment::all()) {
			return $this->errorResponse('No payments found', 404);
		}
		return $this->successResponse("Payments fetched", $payments);
	}

	public function store(Request $request)
	{
		$user = auth()->user();
		if (!$user->stripe_id) {
			return $this->errorResponse('User does not have a stripe id', 404);
		}
		try {
			$payment = Payment::create([
				"user_id" => $user->id,
			]);

		} catch (ApiErrorException $e) {
			return $this->errorResponse('Error creating payment.', $e->getError(), 400);
		}

		return $this->successResponse("Payment successful", $payment, 201);
	}

	public function success($param)
	{
		return $this->successResponse("Payment success", $param);
	}

	public function cancel($param)
	{
		return $this->successResponse("Payment cancelled", $param);
	}

	public function createCheckoutSession()
	{
		return Cashier::stripe()->paymentMethods->all([
			'customer' => auth()->user()->stripe_id,
			"type" => "card",
		]);
	}

	public function createCard()
	{
		try {
			$session = Cashier::stripe()->checkout->sessions->create([
				//'line_items' => $line_items,
				"customer" => auth()->user()->stripe_id,
				'mode' => 'setup',
				'success_url' => env("APP_URL") . 'success',// TODO: Change the route
				'cancel_url' => env("APP_URL") . 'cancel',// TODO: Change the route
				'payment_method_types' => ['card']
			]);
		} catch (ApiErrorException $e) {
			return $this->errorResponse("Api Error", ["error" => $e->getMessage()], 401);
		}

		return $this->successResponse("Success", ["data" => $session], 200);

	}

	public function test()
	{
		$product = Product::find(1);
		return Cashier::stripe()->checkout->sesion([
			'customer' => auth()->user()->stripe_id,
			"type" => "card",
			'line_items' => [
				[
					"amount" => 100

				],
			],
			'mode' => 'payment',// TODO: Change the route
			//'success_url' => env("URL_FRONTEND") . 'success',
			//'cancel_url' => env("URL_FRONTEND") . 'cancel',

		]);
	}


}
