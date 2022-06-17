<?php

namespace App\Http\Resources;

use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LineItemResource extends JsonResource
{
	/**
	 * @param Request $request
	 * @return array
	 */
	public function toArray($request)
	{
		$product = Product::find($this->product_id);
		$payment = Payment::find($this->payment_id);
		return [
			'id' => $this->id,
			'product' => new ProductResource($product),
			'quantity' => $this->quantity,
			"order" => $this->order_id,
			"total" => $product->price * $this->quantity,

		];
	}


}
