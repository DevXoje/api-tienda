<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
	public function toArray($request)
	{
		return [

			"id" => $this->id,
			"name" => $this->name,
			"description" => $this->description,
			"price" => $this->price,
			"stock" => $this->stock,
			"created_ago" => $this->created_at->diffForHumans(),
			"updated_ago" => $this->created_at->diffForHumans(),
			"image" => $this->image,
		];
	}
}
