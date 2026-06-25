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
        Schema::table('returns_products', function (Blueprint $table) {
            $table->integer('usable_quantity')->default(0)->after('variant_value');
            $table->integer('damaged_quantity')->default(0)->after('usable_quantity');
        });

        // Parse existing notes to separate usable and damage quantities if possible
        $returns = DB::table('returns_products')->get();
        foreach ($returns as $return) {
            $usable = 0;
            $damage = 0;
            $notes = $return->notes;

            if ($notes) {
                // Check if notes match "Usable: X"
                if (preg_match('/Usable:\s*(\d+)/i', $notes, $matches)) {
                    $usable = (int)$matches[1];
                }
                // Check if notes match "Damaged: Y" or "Damage: Y"
                if (preg_match('/Damage(?:d)?:\s*(\d+)/i', $notes, $matches)) {
                    $damage = (int)$matches[1];
                }
            }

            // Fallback: if both are 0 but return_quantity > 0, assume all was usable
            if ($usable === 0 && $damage === 0 && $return->return_quantity > 0) {
                $usable = $return->return_quantity;
            }

            DB::table('returns_products')
                ->where('id', $return->id)
                ->update([
                    'usable_quantity' => $usable,
                    'damaged_quantity' => $damage,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('returns_products', function (Blueprint $table) {
            $table->dropColumn(['usable_quantity', 'damaged_quantity']);
        });
    }
};
