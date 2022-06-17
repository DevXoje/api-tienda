<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardResource;
use App\Http\Resources\UserResource;
use App\Models\LineItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class PaymentControllerTest extends ApiController
{
	public function __construct()
	{
		$this->middleware('auth:api');
	}

	public function acociactePaymentMethodToUser(Request $request)
	{
		$user = auth()->user();
		try {
			$intent = Cashier::stripe()->setupIntents->create([
				"customer" => $user->stripe_id,
				'payment_method_types' => ['card']
			]);
		} catch (ApiErrorException $e) {
			return $this->errorResponse($e->getMessage(), $e->getCode());
		}
		return $this->successResponse(['client_secret' => $intent->client_secret]);

	}

	public function addPayment(Request $request)
	{

		$user = auth()->user();
		/*return $user;
		try {
			$stripe_customer = Cashier::stripe()->customers->retrieve($user->stripe_id);
			Cashier::stripe()->paymentMethods->create( 'type' => 'card',
  				'card' => [
				'number' => '4242424242424242',
				'exp_month' => 6,
				'exp_year' => 2023,
				'cvc' => '314',
			],);
		} catch (ApiErrorException $e) {
		}*/

	}

	public function init_checkout()
	{

	}

	public function complete_checkout()
	{
		$user = auth()->user();
		try {
			$payment_methods = Cashier::stripe()->paymentMethods->all([
				"customer" => $user->stripe_id,
				'type' => 'card']);
		} catch (ApiErrorException $e) {
			return $this->errorResponse($e->getMessage(), $e->getCode());
		}
		return $this->successResponse("Payment methods", $payment_methods);

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

	public function test(Request $request)
	{
		$user = auth()->user();
		$intent = $user->createSetupIntent([
			"confirm" => true,
			"customer" => $user->stripe_id,
			"payment_method_types" => ["card"],
			"description" => "Test payment",
			//"payment_method" => $this->createPaymentMethod()->id,
			//"usage" => "off_session",//on_session or off_session
		]);
		return $this->successResponse("Payment success", $intent);
	}

// Version anterior copiada

	public function createSetupIntent()
	{
		$user = auth()->user();
		$user_data = new UserResource($user);
		$card_data = new CardResource($user->card);
		return [
			"confirm" => true,
			"customer" => $user->stripe_id,
			"payment_method_types" => ["card"],
			"description" => "Test payment",
			"payment_method" => $this->createPaymentMethod()->id,
			//"usage" => "off_session",//on_session or off_session
		];
	}

	public function createPaymentMethod()
	{
		$user_data = new UserResource(auth()->user());
		//$card = $this->createCard($user_data);//TODO: create card
		//$card_data = new CardResource($user->card);
		return [
			"billing_details" => [
				"address" => $user_data->address,
				"email" => $user_data->email,
				"name" => $user_data->name,
				"phone" => $user_data->phone,
			],
			/*"card" => [
				[
					...$card_data,
					...$user_data->adress,
					"customer" => $user->stripe_id,
				],
				"checks" => [
					"address_line1_check" => "pass",
					"address_postal_code_check" => "pass",
					"cvc_check" => "pass",
				],
				"funding" => "credit",

			],*/
			"customer" => $user_data->stripe_id,
			"type" => "card",
		];
		//Cashier::stripe()->paymentMethods->create($user->stripe_id, $card);


	}

// DEPENDE de createPaymentMethod

	public function checkout()
	{
		$user = auth()->user();
		if ($user->isAdmin()) {
			return $this->errorResponse('Unauthorized', [
				'error' => 'Unauthorized for admin',
				'code' => '401',
			], 401);
		}
		$line_items = [];
		$payment = $user->payments()->where('status', "=", 'pending')->get()->first();
		if (!$payment) {
			try {
				$newPayment = Payment::create([
					'user_id' => $user->id,
				]);
				$payment = $newPayment;

			} catch (ApiErrorException $e) {
				return $this->errorResponse($e->getMessage(), []);
			}
			$order_items = [
				['name' => 'Producto 1', 'price' => '10', 'quantity' => '1'],
				['name' => 'Producto 2', 'price' => '20', 'quantity' => '2'],
				['name' => 'Producto 3', 'price' => '30', 'quantity' => '3'],
			];
		} else {
			$order_items = $payment->order_items;
			if ($order_items) {
				for ($i = 0; $i < count($order_items); $i++) {
					$item = $order_items[$i];
					$line_items[] = [
						'name' => $item["name"],
						'amount' => $item["price"] * 100,
						'currency' => 'eur',
						'quantity' => $item["quantity"]
					];

				}
				foreach ($line_items as $line_item) {
					$line_itemStore = LineItem::create($line_item);
					$payment->addItem($line_item);
				}
			} else {

			}


		}

		//$customer = User::find($user->id);

		try {
			$session = Cashier::stripe()->checkout->sessions->create([
				'line_items' => $line_items,
				'mode' => 'payment',// TODO: Change the route
				//'success_url' => env("URL_FRONTEND") . 'success',
				//'cancel_url' => env("URL_FRONTEND") . 'cancel',
				"customer" => $user->stripe_id,
			]);
			$payment->update([
				'status' => 'unpaid',
				'session_id' => $session->id,
			]);

			//return Redirect::to($session->url);
			return $this->successResponse("Order Sync with Checkout", $session);

		} catch (ApiErrorException $e) {
			return $this->errorResponse(['message' => 'Stripe Checkout FAILS. Exception ==> ' . $e->getMessage()], 404);
		}
	}

// DEPENDE de Card

	public function checkSuccess(Request $request)
	{
		$intent = $request->intent;
		$order = Payment::where('url', $intent)->first();
		$order->status = 'paid';
		$order->save();
		return $this->successResponse('Order Successfully Paid');
	}

// DEPENDE de createPaymentMethod

	public function checkoutSession()
	{
		$card = $this->createCard();
		$user = auth()->user();
		Cashier::stripe()->paymentMethods->create($user->stripe_id, $card);
		$data = [
			"cancel_url" => "http://localhost:8000/",
			"customer" => $user->stripe_id,
			"mode" => "setup",//payment or setup
			"payment_intent" => $this->createPaymentMethod(),
			"payment_method_types" => ["card"],
			"payment_status" => "unpaid",//paid or unpaid or no_payment_required
			"success_url" => "http://localhost:8000/",
			"currency" => "eur",
		];

	}

// DEPENDE de createPaymentMethod

	public function createPaymentIntent()
	{
		$user = auth()->user();
		$user_data = new UserResource($user);
		$card_data = new CardResource($user->card);
		$payment_method = $this->createPaymentMethod();
		return [
			"customer" => $user->stripe_id,
			"amount" => 100,
			"currency" => "eur",
			'payment_method_types' => [$payment_method->type],
			"confirmation_method" => "automatic",
			"description" => "Test payment",
			"payment_method" => $payment_method->id,
		];
		//Cashier::stripe()->paymentIntents->create();
	}

}
