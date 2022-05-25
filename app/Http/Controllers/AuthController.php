<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class AuthController extends ApiController
{
    public function register(Request $request) {

        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed'
        ]);
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password'])
        ]);


        $token = $user->createToken('myapptoken')->plainTextToken;
        try {
            $stripe_customer = Cashier::stripe()->customers->create([
                'email' => $user->email,
            ]);
        } catch (ApiErrorException $e) {
            return response( $e->getMessage());
        }
        $user->update(['stripe_id'=>$stripe_customer->id]);
        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
        //return $response;
    }


    public function edit(Request $request) {
        $fields = $request->validate([
            'name' => 'string',
            'email' => 'string|unique:users,email',
            'password' => 'string|confirmed'
        ]);
        $user = User::find(auth()->user()->id);
        $user->update($fields);


        $response = [
            'user' => $user,
        ];

        return response($response, 201);
    }
    /*JWT VERSIOM*/

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
        // santum version

        /*$fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if(!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);*/

    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);

        // santum version
        //auth()->user()->tokens()->delete();
        //return [            'message' => 'Logged out'        ];
    }


    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
