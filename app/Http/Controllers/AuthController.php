<?php

namespace App\Http\Controllers;


use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class AuthController extends ApiController
{

	/*JWT VERSIOM*/

	public function __construct()
	{// TODO add verify middleware
		$this->middleware(['auth:api'], ['except' => ['login', 'store', 'removeAllCustomers', 'getAllPrices', 'redirect']]);
	}

	public function redirect()
	{
		return $this->errorResponse('Unauthorized', [], 401);
	}

	public function index()
	{
		if (!auth()->user()->isAdmin()) {
			return $this->errorResponse('Unauthorized', [], 401);
		} elseif (!$users = User::all()) {
			return $this->errorResponse('No se encontraron Users', 404);
		}
		return $this->successResponse("Lista de Users", $users);
	}

	public function show(Request $request, $id)
	{
		try {
			if (!$customer = User::find($id)) {
				return $this->errorResponse('User not found', [], 404);
			} else if (auth()->user()->id != $customer->id && !auth()->user()->isAdmin()) {
				return $this->errorResponse('Unauthorized', [], 401);
			} else {
				return $this->successResponse('User found', new UserResource($customer));
			}
		} catch (Exception $e) {
			return $this->errorResponse($e->getMessage(), [], 500);
		}

	}

	public function store(Request $request)
	{

		$data = array_merge(
			$this->validate($request, [
				'name' => 'required|string|between:2,100',
				'email' => 'required|string|email|max:100|unique:users',
				'password' => 'required|string|confirmed|between:2,100',
				//'phone' => 'required|between:2,100',
				//'official_doc' => 'required|string|between:2,100',
				//'address' => 'required|string|between:2,100',
				//'city' => 'required|string|between:2,100',
				//'country' => 'required|string|between:2,100',
				//'postal_code' => 'required|string|between:2,100',
				//'state' => 'required|string|between:2,100',
			]),
			['password' => bcrypt($request->password)]
		);

		try {
			$user = User::create($data);
			$token = auth()->login($user);
		} catch (ApiErrorException $e) {
			return $this->errorResponse('Error creating user.', $e->getError(), 400);
		}
		return $this->successResponse('User created & logged successfully.', ['token' => $token, 'user' => new UserResource($user)], 201);


	}

	public function login(Request $request)
	{
		$credentials = $this->validate($request, [
			'email' => 'required|string|email|max:100',
			'password' => 'required|string|between:2,100',
		]);
		try {
			if (!$token = auth()->attempt($credentials)) {
				return $this->errorResponse('Unauthorized', $credentials, 401);
			}
			return $this->successResponse('User successfully logged.', ['token' => $token, 'auth' => new UserResource(auth()->user())], 201);

		} catch (QueryException $e) {
			return $this->errorResponse($e->getMessage(), [
				'fail' => $e->getSql(),
			], 500);
		} catch (Exception $e) {
			return $this->errorResponse($e->getMessage(), [], 500);
		}
	}

	public function complete(Request $request)
	{
		if (!$user = auth()->user()) {
			return $this->errorResponse('User Not founded', $request->all(), 404);
		}
		if ($user->official_doc) {
			return $this->errorResponse('User is already Completed', new UserResource($user));
		}
		$oldUser = clone $user;

		$fields = $this->validate($request, [
			'city' => 'required|string',
			'country' => 'required|string',
			'address' => 'required|string',
			'postal_code' => 'required|string',
			'state' => 'required|string',
			'phone' => 'required|string',
			'official_doc' => 'required|string',

		]);

		$user->update($fields);
		//$user->update($request->all());
		return $this->successResponse('User updated successfully.', ['oldUser' => new UserResource($oldUser), 'user' => new UserResource($user)], 201);
	}

	public function update(Request $request, $id)
	{
		$fields = $this->validate($request, [
			'name' => 'string|between:2,100',
			'email' => 'string|email|max:100|unique:users',
			'password' => 'string|confirmed|between:2,100',
			'phone' => 'between:2,100',
			'official_doc' => 'string|between:2,100',
			'address' => 'string|between:2,100',
			'city' => 'string|between:2,100',
			'country' => 'string|between:2,100',
			'postal_code' => 'string|between:2,100',
			'state' => 'string|between:2,100',
		]);
		if (!$user = User::find($id)) {
			return $this->errorResponse('User Not founded', $fields, 404);
		}
		if (!$fields) {
			return $this->errorResponse('No fields to update', $fields, 400);
		}
		$oldUser = clone $user;
		$user->update($fields);
		return $this->successResponse('User updated successfully.', ['oldUser' => new UserResource($oldUser), 'user' => new UserResource($user)], 201);
	}

	public function destroy($id)
	{
		if (!$user = User::find($id)) {
			return $this->errorResponse('User Not founded', [], 404);
		}
		$user->delete();
		return $this->successResponse('User deleted successfully.', [], 201);
	}


	public function logout()
	{
		auth()->logout();

		return response()->json(['message' => 'Successfully logged out']);

	}

	public function restore()
	{

		if ($auth = auth()->user()) {
			$id = $auth->id;
			if ($customer = (User::find($id))) {
				$customer_data = new UserResource($customer);
				return $this->successResponse('User Profile successfully fetched', $customer_data);
			} else {
				return $this->errorResponse('Bad User ID', 401);
			}

		} else {
			return $this->errorResponse('No User found', 401);
		}

	}

	public function me()
	{
		return response()->json(auth()->user());
	}

	public function refresh()
	{
		return $this->respondWithToken(auth()->refresh());
	}

	protected function respondWithToken($token)
	{
		return $this->successResponse('User logged in successfully.', [
			'access_token' => $token,
			'token_type' => 'bearer',
			'expires_in' => auth()->factory()->getTTL() * 60
		], 200);
	}

	public function removeAllCustomers()
	{
		try {
			$customers = Cashier::stripe()->customers->all(['limit' => 30])->data;
			foreach ($customers as $customer) {
				Cashier::stripe()->customers->delete($customer->id);
			}
			return $this->successResponse('All Customers deleted');
		} catch (ApiErrorException $e) {
			return $this->errorResponse('No stripe paymentIntent found. Exception ==> ' . $e->getMessage(), 404);
		}
	}

	public function getAllPrices()
	{
		return Cashier::stripe()->prices->all()->data;
		foreach ($prices as $price) {
			Cashier::stripe()->prices->retrieve($price->id);
		}
	}
}
