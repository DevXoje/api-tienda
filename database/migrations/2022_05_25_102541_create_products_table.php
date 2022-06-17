<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Stripe\Exception\ApiErrorException;

class CreateProductsTable extends Migration
{
	public function up()
	{
		Schema::create('products', function (Blueprint $table) {
			$table->id();

			$table->string('name');
			$table->text('description');
			$table->decimal('price', 8, 2);
			$table->unsignedInteger('stock')->default(0);
			$table->string('image')->nullable();
			$table->string('stripe_id')->nullable();

			$table->timestamps();
		});
		$this->mockProducts();

	}

	public function mockProducts()
	{
		try {
			$products = [
				Product::create([
					"name" => "Producto 1",
					"description" => "description del product 1",
					"price" => 11.5,
					"stock" => 10,
					"image" => "https://picsum.photos/id/700/900/500",
				]),
				Product::create([
					"name" => "Producto 2",
					"description" => "description del product 2",
					"price" => 10.5,
					"stock" => 1,
					"image" => "https://picsum.photos/id/500/900/500",
				]),
				Product::create([
					"name" => "Producto 3",
					"description" => "description del product 3",
					"price" => 20.55,
					"stock" => 20,
					"image" => "https://picsum.photos/id/300/900/500"
				]),
				Product::create([
					"name" => "Producto 4",
					"description" => "description del product 4",
					"price" => 1.55,
					"stock" => 2,
					"image" => "https://picsum.photos/id/100/900/500"
				]),
			];
		} catch (ApiErrorException $e) {
			throw $e;
		}
	}

	public function down()
	{
		Schema::dropIfExists('products');
	}
}
