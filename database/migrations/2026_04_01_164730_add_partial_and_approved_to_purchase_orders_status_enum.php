<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the status enum to include 'partial' and 'approved'
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('pending','complete','received','cancelled','partial','approved') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('pending','complete','received','cancelled') DEFAULT 'pending'");
    }
};
