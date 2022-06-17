<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Cashier;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Stripe\Exception\ApiErrorException;
use Tymon\JWTAuth\Contracts\JWTSubject;
use function Illuminate\Events\queueable;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
	use HasApiTokens, HasFactory, Notifiable, Billable, TwoFactorAuthenticatable;

	// TODO: Define the getter attributes of the user for stripe

	/*protected $attributes=[
		'name',
		'email',
		'password',
		'stripe_id',
		'official_doc',
		'phone',
		'address',
		'city',
		'country',
		'postal_code',
		'state',

	];*/
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'name',
		'email',
		'password',
		'stripe_id',
		'official_doc',
		'phone',
		'address',
		'city',
		'country',
		'postal_code',
		'state',

	];
	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [
		'password',
		'remember_token',
	];
	protected $casts = [
		'email_verified_at' => 'datetime',
	];

	public static function create($user_data)
	{
		$user = new User($user_data);
		/*$user = new User([
			'name' => $user_data['name'],
			'email' => $user_data['email'],
			'password' => $user_data['password'],
			//'official_doc' => $user_data['official_doc'],
			//'phone' => $user_data['phone'],
			//'address' => $user_data['address'],
			//'city' => $user_data['city'],
			//'country' => $user_data['country'],
			//'postal_code' => $user_data['postal_code'],
			//'state' => $user_data['state'],
		]);*/

		$user->save();

		//$customer->id = $user->id;
		try {
			$customer_stripe = Cashier::stripe()->customers->create([
				'email' => $user->email,
			]);
			$user->update(['stripe_id' => $customer_stripe->id]);
			//Mail::to($user->email)->send(new NewUserNotification($user->name));
			//event(new Registered($user));
		} catch (ApiErrorException $e) {
			$user->delete();
			throw $e;
		}

		return $user;
	}

	protected static function booted()
	{
		parent::boot();
		static::updated(queueable(function ($customer) {
			if ($customer->hasStripeId()) {
				$customer->syncStripeCustomerDetails();
			}
		}));
		static::deleting(function ($customer) {
			if ($customer->hasStripeId()) {
				Cashier::stripe()->customers->delete($customer->stripe_id);
			}
		});
	}

	public function payments()
	{
		return $this->hasMany(Payment::class);
	}

	public function stripeName()
	{
		return $this->name;
	}

	public function stripeEmail()
	{
		return $this->email;
	}

	public function stripeAddress()
	{
		return [
			'line1' => $this->address,
			'city' => $this->city,
			'country' => $this->country,
			'postal_code' => $this->postal_code,
			'state' => $this->state,
		];
	}

	public function stripePhone()
	{
		return $this->phone;
	}

	public function stripeDescription()
	{
		return "User {$this->name}";
	}

	public function isAdmin()
	{
		return $this->role === 'admin';
	}
	// Rest omitted for brevity

	/**
	 * Get the identifier that will be stored in the subject claim of the JWT.
	 *
	 * @return mixed
	 */
	public function getJWTIdentifier()
	{
		return $this->getKey();
	}

	/**
	 * Return a key value array, containing any custom claims to be added to the JWT.
	 *
	 * @return array
	 */
	public function getJWTCustomClaims()
	{
		return [];
	}

	public function newPayment()
	{

	}
}
