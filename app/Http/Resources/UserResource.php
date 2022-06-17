<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
	/**
	 * @param Request $request
	 * @return array
	 */
	public function toArray($request)
	{
		return [
			'id' => $this->id,
			"name" => $this->name,
			"email" => $this->email,
			"created_ago" => $this->created_at->diffForHumans(),
			"updated_ago" => $this->created_at->diffForHumans(),
			"official_doc" => $this->official_doc,
			"phone" => $this->phone,
			'address' => [
				'city' => $this->city,
				'country' => $this->country,
				'line1' => $this->address,
				'postal_code' => $this->postal_code,
				'state' => $this->state,
			],
			"role" => $this->role,
		];
	}


}
