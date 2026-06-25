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
		Schema::create('debt_installments', function (Blueprint $table) {
			$table->id();
			$table->foreignId('debt_id')->constrained()->onDelete('cascade');
			$table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
			$table->integer('installment_number');
			$table->date('due_date');
			$table->bigInteger('principal_amount')->default(0);
			$table->bigInteger('interest_amount')->default(0);
			$table->bigInteger('service_fee')->default(0);
			$table->bigInteger('vat_amount')->default(0);
			$table->bigInteger('penalty_amount')->default(0);
			$table->bigInteger('total_amount')->default(0);
			$table->string('status')->default('unpaid');
			$table->timestamp('paid_at')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('debt_installments');
	}
};
