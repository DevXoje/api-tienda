<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
	public function toArray($request)
	{
		$customer = User::find($this->user_id);
		$line_items = $this->line_items;
		$total_amount = 0;
		foreach ($line_items as $line_item) {
			$total_amount += $line_item->amount;
		}
		return [
			'id' => $this->id,
			'customer' => new UserResource($customer),
			"payment_method" => $this->payment_method,
			"status" => $this->status,
			"success_url" => $this->success_url,
			"cancel_url" => $this->cancel_url,
			"total" => $total_amount,

		];
	}


}
