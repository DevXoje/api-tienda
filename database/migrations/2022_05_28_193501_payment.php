<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up()
	{
		Schema::create('payments', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('user_id')->nullable();
			$table->string('stripe_id')->nullable();
			//$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->string('status')->default('pending');

			$table->timestamps();
		});
		Schema::create('line_items', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('payment_id');
			//$table->foreign('payment_id')->references('id')->on('payments');
			$table->unsignedBigInteger('product_id');
			//$table->foreign('product_id')->references('id')->on('products');
			$table->unsignedBigInteger('quantity')->default(1);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::dropIfExists('payments');
		Schema::dropIfExists('line_items');

	}
};
