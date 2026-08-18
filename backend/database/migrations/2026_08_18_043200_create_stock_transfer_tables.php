<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('transfer_number', 80);
            $table->foreignId('source_location_id')
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->foreignId('transit_location_id')->nullable()
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('dispatched_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('received_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelled_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('dispatch_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('receive_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('cancel_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->string('dispatch_idempotency_key', 120)->nullable();
            $table->string('receive_idempotency_key', 120)->nullable();
            $table->string('cancel_idempotency_key', 120)->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'transfer_number']);
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_transfer_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_packaging_id')->nullable()
                ->constrained('product_packagings')->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()
                ->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('entered_quantity', 20, 6);
            $table->decimal('conversion_to_base_snapshot', 18, 6);
            $table->decimal('quantity_base', 24, 6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
