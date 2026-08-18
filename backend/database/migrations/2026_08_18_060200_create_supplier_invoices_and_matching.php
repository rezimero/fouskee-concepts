<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number', 160);
            $table->string('normalized_invoice_number', 160);
            $table->date('invoice_date');
            $table->char('currency_code', 3);
            $table->string('status', 30)->default('recorded')->index();
            $table->decimal('subtotal_amount', 24, 6);
            $table->decimal('tax_amount', 24, 6)->default(0);
            $table->decimal('total_amount', 24, 6);
            $table->string('idempotency_key', 120);
            $table->char('idempotency_fingerprint', 64);
            $table->foreignId('recorded_by_user_id')
                ->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('latest_match_run_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['organization_id', 'supplier_id', 'normalized_invoice_number'],
                'supplier_invoice_duplicate_guard',
            );
            $table->unique(
                ['organization_id', 'idempotency_key'],
                'supplier_invoice_idempotency_unique',
            );
        });

        Schema::create('supplier_invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('supplier_invoice_id')
                ->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignId('purchase_order_line_id')->nullable()
                ->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_packaging_id')->nullable()
                ->constrained('product_packagings')->restrictOnDelete();
            $table->string('product_sku_snapshot', 80);
            $table->string('product_name_snapshot', 200);
            $table->string('packaging_name_snapshot', 150)->nullable();
            $table->decimal('conversion_to_base_snapshot', 18, 6);
            $table->decimal('invoiced_quantity', 20, 6);
            $table->decimal('invoiced_quantity_base', 24, 6);
            $table->decimal('unit_price', 24, 6);
            $table->decimal('unit_price_base', 24, 6);
            $table->decimal('line_amount', 24, 6);
            $table->unique(['supplier_invoice_id', 'line_number']);
        });

        Schema::create('procurement_match_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_invoice_id')
                ->constrained()->restrictOnDelete();
            $table->unsignedInteger('run_number');
            $table->string('result', 30)->index();
            $table->decimal('expected_amount', 24, 6);
            $table->decimal('invoiced_amount', 24, 6);
            $table->decimal('amount_variance', 24, 6);
            $table->foreignId('policy_version_id')
                ->constrained('procurement_policy_versions')->restrictOnDelete();
            $table->string('idempotency_key', 120);
            $table->char('idempotency_fingerprint', 64);
            $table->foreignId('matched_by_user_id')
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('matched_at', 6);
            $table->timestamps();

            $table->unique(['supplier_invoice_id', 'run_number']);
            $table->unique(
                ['organization_id', 'idempotency_key'],
                'proc_match_idempotency_unique',
            );
        });

        Schema::create('procurement_match_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_run_id')
                ->constrained('procurement_match_runs')->restrictOnDelete();
            $table->foreignId('supplier_invoice_line_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()
                ->constrained()->restrictOnDelete();
            $table->string('result', 30);
            $table->decimal('ordered_quantity_base', 24, 6)->nullable();
            $table->decimal('received_quantity_base', 24, 6);
            $table->decimal('invoiced_quantity_base', 24, 6);
            $table->decimal('cumulative_invoiced_quantity_base', 24, 6);
            $table->decimal('ordered_unit_price_base', 24, 6)->nullable();
            $table->decimal('invoiced_unit_price_base', 24, 6);
            $table->decimal('expected_amount', 24, 6);
            $table->decimal('invoiced_amount', 24, 6);
            $table->decimal('quantity_variance_base', 24, 6);
            $table->decimal('price_variance_base', 24, 6);
            $table->decimal('amount_variance', 24, 6);
            $table->json('exception_codes')->nullable();
        });

        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->foreign('latest_match_run_id')
                ->references('id')
                ->on('procurement_match_runs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->dropForeign(['latest_match_run_id']);
        });

        Schema::dropIfExists('procurement_match_lines');
        Schema::dropIfExists('procurement_match_runs');
        Schema::dropIfExists('supplier_invoice_lines');
        Schema::dropIfExists('supplier_invoices');
    }
};
