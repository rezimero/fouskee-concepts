<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('goods_receipt_number', 80);
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_location_id')
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->string('status', 30)->index();
            $table->timestamp('received_at', 6);
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 120);
            $table->char('idempotency_fingerprint', 64);
            $table->foreignId('policy_version_id')
                ->constrained('procurement_policy_versions')->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('rejected_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('inventory_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('reversal_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'goods_receipt_number']);
            $table->unique(
                ['organization_id', 'idempotency_key'],
                'goods_receipt_idempotency_unique',
            );
            $table->index(['organization_id', 'purchase_order_id', 'status']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('goods_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_line_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_packaging_id')->nullable()
                ->constrained('product_packagings')->restrictOnDelete();
            $table->decimal('received_quantity', 20, 6);
            $table->decimal('conversion_to_base_snapshot', 18, 6);
            $table->decimal('received_quantity_base', 24, 6);
            $table->decimal('unit_cost_base_snapshot', 24, 6);
            $table->string('lot_code', 100)->nullable();
            $table->date('expiry_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
    }
};
