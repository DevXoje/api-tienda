<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class ProductsController extends ApiController
{


	public function __construct()
	{
		$this->middleware(['auth:api'], ['except' => ['index', 'show', 'search']]);
	}

	public function index()
	{
		if (!$products = Product::all()) {
			return $this->errorResponse('No se encontraron productos', 404);
		}
		return $this->successResponse("Lista de productos", ProductResource::collection($products));
	}


	public function store(Request $request)
	{
		if (!auth()->user()->isAdmin()) {
			return $this->errorResponse('Unauthorized', [], 401);
		}
		$validator = Validator::make($request->all(), [
			'name' => 'required|unique:products',
			'description' => 'required',
			'price' => 'required|numeric:|min:0',
			'stock' => 'required|integer:|min:0',
			//"image" => "required",
		]);
		if ($validator->fails()) {
			return $this->errorResponse('Validation error.', $validator->errors(), 400);
		}


		try {
			$product = Product::create($validator->validated());
			return $this->successResponse("Product Created", new ProductResource($product), 201);

		} catch (ApiErrorException $e) {
			return $this->errorResponse('Error creating product.', $e->getError(), 400);

		}
	}


	public function show($id)
	{
		if (!$product = Product::find($id)) {
			return $this->errorResponse('Product not found', 404);
		}
		return $this->successResponse("Product Fetched", new ProductResource($product));

	}


	public function update(Request $request, $id)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'unique:products',
			'description' => 'string',
			'price' => 'numeric:|min:0',
			//"image" => "required",
		]);
		if (!$product = Product::find($id)) {
			return $this->errorResponse('Product not found', 404);
		}
		$old_product = $product->toArray();
		$product->update($validator->validated());
		return $this->successResponse("Product Updated", [
			'old' => new ProductResource($old_product),
			'updated' => new ProductResource($product),
		]);

	}


	public function destroy($id)
	{
		if (!$product = Product::find($id)) {
			return $this->errorResponse('Product not found', 404);
		}
		$product->delete();
		return $this->successResponse("Product Deleted", $product, 200);
	}


	public function search($name)
	{
		return Product::where('name', 'like', '%' . $name . '%')->get();
	}

	public function removeAllProducts()
	{
		try {
			$customers = Cashier::stripe()->products->all(['limit' => 100])->data;
			foreach ($customers as $customer) {

				Cashier::stripe()->products->delete($customer->id);
			}
			return $this->successResponse('All Products deleted');
		} catch (ApiErrorException $e) {
			return $this->errorResponse('No stripe paymentIntent found. Exception ==> ' . $e->getMessage(), 404);
		}
	}

}
