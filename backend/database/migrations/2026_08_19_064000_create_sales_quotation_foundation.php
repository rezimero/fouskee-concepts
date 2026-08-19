<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('quotation_number', 80);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('price_list_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->string('settlement_type', 30);
            $table->char('currency_code', 3);
            $table->decimal('subtotal_amount', 18, 6);
            $table->decimal('total_amount', 18, 6);
            $table->date('valid_until');
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 120);
            $table->char('idempotency_fingerprint', 64);
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sent_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('accepted_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('converted_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'quotation_number']);
            $table->unique(['organization_id', 'idempotency_key']);
            $table->index(['organization_id', 'customer_id', 'status']);
        });

        Schema::create('sales_quotation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_quotation_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('line_number');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_packaging_id')->constrained()->restrictOnDelete();
            $table->string('product_sku_snapshot', 80);
            $table->string('product_name_snapshot', 200);
            $table->string('packaging_name_snapshot', 150);
            $table->decimal('conversion_to_base_snapshot', 18, 6);
            $table->decimal('quoted_quantity', 24, 6);
            $table->decimal('quoted_quantity_base', 24, 6);
            $table->decimal('list_unit_price', 18, 6);
            $table->decimal('floor_unit_price', 18, 6);
            $table->decimal('effective_unit_price', 18, 6);
            $table->decimal('line_total', 18, 6);
            $table->boolean('price_exception')->default(false);
            $table->timestamps();

            $table->unique(['sales_quotation_id', 'line_number']);
        });

        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->foreignId('source_quotation_id')->nullable()
                ->after('price_list_id')
                ->constrained('sales_quotations')
                ->restrictOnDelete();
            $table->unique('source_quotation_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('source_quotation_id');
        });

        Schema::dropIfExists('sales_quotation_lines');
        Schema::dropIfExists('sales_quotations');
    }
};
