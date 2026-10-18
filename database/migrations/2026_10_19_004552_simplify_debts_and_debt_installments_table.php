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
        Schema::table('debts', function (Blueprint $table) {
            $table->bigInteger('total_payable_amount')->default(0)->after('principal_amount');
            $table->dropColumn(['admin_fee', 'disbursement_amount', 'service_fee_rate', 'platform_name', 'description']);
        });

        Schema::table('debt_installments', function (Blueprint $table) {
            $table->dropColumn(['service_fee', 'vat_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debt_installments', function (Blueprint $table) {
            $table->bigInteger('service_fee')->default(0)->after('interest_amount');
            $table->bigInteger('vat_amount')->default(0)->after('service_fee');
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->string('platform_name')->after('code');
            $table->text('description')->nullable()->after('status');
            $table->bigInteger('admin_fee')->default(0)->after('principal_amount');
            $table->bigInteger('disbursement_amount')->default(0)->after('admin_fee');
            $table->decimal('service_fee_rate', 25, 5)->default(0.00)->after('interest_rate');
            $table->dropColumn('total_payable_amount');
        });
    }
};
