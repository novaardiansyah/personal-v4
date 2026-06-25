<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('debts', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->onDelete('cascade');
			$table->foreignId('payment_account_id')->constrained()->onDelete('cascade');
			$table->string('code')->unique();
			$table->string('platform_name');
			$table->string('name');
			$table->bigInteger('principal_amount');
			$table->bigInteger('admin_fee')->default(0);
			$table->bigInteger('disbursement_amount');
			$table->decimal('interest_rate', 25, 5)->default(0.00);
			$table->decimal('service_fee_rate', 25, 5)->default(0.00);
			$table->integer('tenor');
			$table->date('start_date');
			$table->string('status')->default('ongoing');
			$table->text('description')->nullable();
			$table->softDeletes();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('debts');
	}
};
