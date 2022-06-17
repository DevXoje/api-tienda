<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function (Blueprint $table) {
			$table->id();
			$table->string('role')->default('customer');
			$table->string('name');
			$table->string('email')->unique();
			$table->string('official_doc')->nullable();
			$table->string('phone')->nullable();
			$table->string('address')->nullable();
			$table->string('city')->nullable();
			$table->string('country')->nullable()->default('ES');
			$table->string('postal_code')->nullable();
			$table->string('state')->nullable()->default('Madrid');
			$table->timestamp('email_verified_at')->nullable();
			$table->string('password');
			$table->rememberToken();
			$table->timestamps();
		});
		$this->mockAdmin();
	}

	private function mockAdmin()
	{
		$user = new User([
			'name' => 'admin',
			//'role' => 'admin',
			'password' => bcrypt('admin'),
			'email' => "dawxoje@gmail.com",
			'official_doc' => '12345678A',
			'phone' => '123456789',
			'address' => 'Calle de la calle',
			'city' => 'Madrid',
			'country' => 'ES',
			'postal_code' => '28001',
			'state' => 'Madrid',
		]);
		$user->save();
		$user->role = 'admin';
		$user->save();


	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('users');
	}
}
