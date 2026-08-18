<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_location_id')
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()
                ->constrained('inventory_lots')->restrictOnDelete();
            $table->string('lot_scope_key', 32)->default('none');
            $table->decimal('quantity_on_hand_base', 24, 6)->default(0);
            $table->decimal('inventory_value', 24, 6)->default(0);
            $table->decimal('weighted_average_unit_cost', 24, 6)->default(0);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamp('projected_at', 6)->nullable();

            $table->unique(
                ['organization_id', 'inventory_location_id', 'product_id', 'lot_scope_key'],
                'inventory_balance_scope_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
