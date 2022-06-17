<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;
use function Illuminate\Events\queueable;

class Payment extends Model
{
	protected $fillable = ['user_id', 'status'/*'payment_method'*/];
	protected $casts = [
		'user_id' => 'integer',
		'status' => 'string',
		//'payment_method' => 'string',
	];

	public static function create($payment_data)
	{
		$customer = User::find($payment_data['user_id']);
		try {
			if (!$stripePayment = Cashier::stripe()->checkout->sessions->create([
				'customer' => $customer->stripe_id,
				"payment_method_types" => ['card'],

				//'payment_method' => $payment_data['payment_method'],
				'success_url' => env("APP_URL") . "success",
				'cancel_url' => env("APP_URL") . "cancel",
				'mode' => 'setup',
				/*'line_items' => [[
					'price_data' => [
						'currency' => 'usd',
						'product_data' => [
							'name' => 'T-shirt',
						],
						'unit_amount' => 2000,
					],
					'quantity' => 1,
				]],*/
			])) {
				return false;
			}
		} catch (ApiErrorException $e) {
			throw $e;
		}
		$payment = new Payment([
			'user_id' => $payment_data['user_id'],
			'mode' => 'payment',

			//'payment_method' => $payment_data['payment_method'],
		]);
		$payment->save();

		return $payment;
	}


	protected static function booted()
	{
		parent::boot();
		static::updated(queueable(function ($payment) {
			if ($payment->hasStripeId()) {
				$payment->syncStripePaymentDetails();
			}
		}));
		static::deleting(function ($payment) {
			if ($payment->hasStripeId()) {
				Cashier::stripe()->paymentIntents->delete($payment->stripe_id);
			}
		});
	}

	public function line_items()
	{
		return $this->hasMany(LineItem::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
