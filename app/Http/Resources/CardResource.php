<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
	/**
	 * @param Request $request
	 * @return array
	 */
	public function toArray($request)
	{
		return [
			"brand" => $this->brand,
			"country" => $this->country,
			"exp_month" => $this->exp_month,
			"exp_year" => $this->exp_year,
			"last4" => $this->last4,

		];
	}


}
