<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class LineItem extends Model
{
	protected $fillable = ['quantity', 'product_id', 'payment_id', 'stock'];
	protected $casts = [
		'quantity' => 'integer',
		'product_id' => 'integer',
		'payment_id' => 'integer',
	];

	public static function getTotal($order_id)
	{
		$total = 0;
		$line_items = LineItem::where('order_id', $order_id)->get();
		foreach ($line_items as $line_item) {
			$total += $line_item->quantity * $line_item->product->price;
		}
		return $total;
	}

	public static function create($line_item_data): LineItem
	{
		$quantity = $line_item_data['quantity'];
		$payment = Payment::find($line_item_data['payment_id']);
		$product = Product::find($line_item_data['product_id']);
		if ($product->stock < $quantity) {
			throw new Exception('Not enough stock');
		}
		$product->update(['stock' => $product->stock - $quantity]);

		$line_item = new LineItem([
			'quantity' => $quantity,
			'product_id' => $product->id,
			'payment_id' => $payment->id,
		]);

		$line_item->save();

		try {
			$oldPaymentStripe = Cashier::stripe()->checkout->sessions->retrieve($payment->stripe_id);
			$paymentStripe = Cashier::stripe()->checkout->sessions->create([
				'line_items' => $payment->line_items(),
				'mode' => 'payment',// TODO: Change the route
				//'success_url' => env("URL_FRONTEND") . 'success',
				//'cancel_url' => env("URL_FRONTEND") . 'cancel',
				"customer" => $payment->user()->stripe_id,
			]);

			$payment->update(["stripe_id" => $paymentStripe->id]);
			//$paymentStripe->charges->create()
		} catch (ApiErrorException $e) {
			$line_item->delete();
			throw $e;
		}
		$line_item->save();
		return $line_item;
	}


	public function payment()
	{
		return $this->belongsTo(Payment::class);
	}

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
