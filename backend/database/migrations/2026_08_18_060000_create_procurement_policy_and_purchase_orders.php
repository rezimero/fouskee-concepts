<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_policy_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->timestamp('effective_from', 6)->index();
            $table->decimal('purchase_order_approval_value_threshold', 24, 6)
                ->default(0);
            $table->decimal('over_receipt_tolerance_percent', 9, 4)->default(0);
            $table->decimal('invoice_quantity_tolerance_percent', 9, 4)->default(0);
            $table->decimal('invoice_price_tolerance_percent', 9, 4)->default(0);
            $table->decimal('invoice_amount_tolerance_percent', 9, 4)->default(0);
            $table->foreignId('created_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(
                ['organization_id', 'effective_from'],
                'proc_policy_org_effective_idx',
            );
        });

        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('purchase_order_number', 80);
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->char('currency_code', 3);
            $table->date('expected_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 24, 6);
            $table->string('idempotency_key', 120);
            $table->char('idempotency_fingerprint', 64);
            $table->foreignId('policy_version_id')->nullable()
                ->constrained('procurement_policy_versions')->restrictOnDelete();
            $table->foreignId('created_by_user_id')
                ->constrained('users')->restrictOnDelete();
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

            $table->unique(['organization_id', 'purchase_order_number']);
            $table->unique(
                ['organization_id', 'idempotency_key'],
                'purchase_order_idempotency_unique',
            );
            $table->index(['organization_id', 'supplier_id', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('purchase_order_id')
                ->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_packaging_id')->nullable()
                ->constrained('product_packagings')->restrictOnDelete();
            $table->string('product_sku_snapshot', 80);
            $table->string('product_name_snapshot', 200);
            $table->string('packaging_name_snapshot', 150)->nullable();
            $table->decimal('conversion_to_base_snapshot', 18, 6);
            $table->decimal('ordered_quantity', 20, 6);
            $table->decimal('ordered_quantity_base', 24, 6);
            $table->decimal('unit_price', 24, 6);
            $table->decimal('unit_price_base', 24, 6);
            $table->decimal('line_total', 24, 6);
            $table->unique(['purchase_order_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('procurement_policy_versions');
    }
};
