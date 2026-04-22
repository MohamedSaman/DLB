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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('grn_number')->nullable()->after('received_date');
            $table->date('grn_date')->nullable()->after('grn_number');
            $table->decimal('discount_amount', 15, 2)->default(0)->nullable()->after('grn_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('grn_number');
            $table->dropColumn('grn_date');
            $table->dropColumn('discount_amount');
        });
    }
};
