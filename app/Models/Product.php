<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;
use function Illuminate\Events\queueable;

class Product extends Model
{
	protected $attributes = [

	];
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'name',
		'description',
		'price',
		'stock',
		'stripe_id',
		'image',
	];
	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [];
	protected $casts = [
		'name' => 'string',
		'description' => 'string',
		'price' => 'float',
		'stock' => 'integer',
		'stripe_id' => 'string',
	];

	public static function create($product_data)
	{

		$product = new Product([
			'name' => $product_data['name'],
			'description' => $product_data['description'],
			'price' => $product_data['price'],
			'stock' => $product_data['stock'],
			'image' => $product_data['image'],
		]);

		$product->save();

		try {

			$product_stripe = Cashier::stripe()->products->create([
				'name' => $product->name,
			]);
			$product_price_stripe = Cashier::stripe()->prices->create([
				'currency' => 'eur',
				'unit_amount' => $product->price * 100,
				'product' => $product_stripe->id,

			]);
			$product->update(['stripe_id' => $product_stripe->id]);
			//Mail::to($user->email)->send(new NewUserNotification($user->name));
			//event(new Registered($user));
		} catch (ApiErrorException $e) {
			$product->delete();
			throw $e;
		}
		$product->save();
		return $product;
	}

	protected static function booted()
	{
		parent::boot();

		static::updated(queueable(function ($product) {
			if ($product->hasStripeId()) {
				$product->syncStripeProductDetails();
			}
		}));
		static::deleting(function ($product) {
			if ($product->hasStripeId()) {
				Cashier::stripe()->products->delete($product->stripe_id);
			}
		});
	}

	public function hasStripeId()
	{
		return !empty($this->stripe_id);
	}

	public function syncStripeProductDetails()
	{
		Cashier::stripe()->products->update($this->stripe_id, [
			'name' => $this->name,
			'description' => $this->description,
		]);

	}

	public function customer()
	{
		return $this->belongsTo(User::class);
	}

	public function stripeName()
	{
		return $this->name;
	}

	public function stripeDescription()
	{
		return $this->description;
	}

	public function getPrice()
	{
		$payload = Cashier::stripe()->products->retrieve($this->stripe_id);
		return $payload->default_price;
	}

}
