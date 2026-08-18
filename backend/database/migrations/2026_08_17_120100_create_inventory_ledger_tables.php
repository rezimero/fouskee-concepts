<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('movement_number', 80);
            $table->string('movement_type', 30)->index();
            $table->timestamp('occurred_at', 6);
            $table->timestamp('posted_at', 6);
            $table->foreignId('posted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('idempotency_key', 120);
            $table->char('idempotency_fingerprint', 64);
            $table->string('source_reference_type', 80)->nullable();
            $table->string('source_reference_public_id', 64)->nullable();
            $table->foreignId('reversal_of_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->text('business_reason')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'movement_number']);
            $table->unique(['organization_id', 'idempotency_key']);
            $table->unique('reversal_of_movement_id', 'inventory_movement_single_reversal');
            $table->index(['organization_id', 'occurred_at']);
        });

        Schema::create('inventory_movement_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_movement_id')
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('inventory_location_id')
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_packaging_id')->nullable()
                ->constrained('product_packagings')->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()
                ->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('entered_quantity', 20, 6);
            $table->decimal('conversion_to_base_snapshot', 18, 6);
            $table->decimal('quantity_base', 24, 6);
            $table->decimal('unit_cost_snapshot', 24, 6)->nullable();
            $table->decimal('inventory_value_delta', 24, 6)->nullable();

            $table->index(['inventory_location_id', 'product_id']);
            $table->index(['product_id', 'inventory_lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movement_lines');
        Schema::dropIfExists('inventory_movements');
    }
};
