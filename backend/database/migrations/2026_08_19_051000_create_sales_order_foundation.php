<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('sales_order_number', 80);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('price_list_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->string('settlement_type', 30);
            $table->char('currency_code', 3);
            $table->decimal('subtotal_amount', 18, 6);
            $table->decimal('total_amount', 18, 6);
            $table->decimal('credit_limit_snapshot', 18, 6)->nullable();
            $table->decimal('credit_exposure_before_snapshot', 18, 6)->nullable();
            $table->json('exception_codes')->nullable();
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 120);
            $table->char('idempotency_fingerprint', 64);
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelled_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'sales_order_number']);
            $table->unique(['organization_id', 'idempotency_key']);
            $table->index(['organization_id', 'customer_id', 'status']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_packaging_id')->constrained()->restrictOnDelete();
            $table->string('product_sku_snapshot', 80);
            $table->string('product_name_snapshot', 200);
            $table->string('packaging_name_snapshot', 150);
            $table->decimal('conversion_to_base_snapshot', 18, 6);
            $table->decimal('ordered_quantity', 24, 6);
            $table->decimal('ordered_quantity_base', 24, 6);
            $table->decimal('list_unit_price', 18, 6);
            $table->decimal('floor_unit_price', 18, 6);
            $table->decimal('effective_unit_price', 18, 6);
            $table->decimal('line_total', 18, 6);
            $table->boolean('price_exception')->default(false);
            $table->timestamps();

            $table->unique(['sales_order_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
