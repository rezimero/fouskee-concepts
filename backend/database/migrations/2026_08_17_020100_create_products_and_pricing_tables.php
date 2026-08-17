<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')
                ->constrained()->restrictOnDelete();
            $table->string('sku', 80);
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->foreignId('brand_id')->nullable()
                ->constrained('brands')->restrictOnDelete();
            $table->foreignId('product_category_id')->nullable()
                ->constrained('product_categories')->restrictOnDelete();
            $table->foreignId('base_unit_id')
                ->constrained('units_of_measure')->restrictOnDelete();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'sku']);
            $table->index(['organization_id', 'status', 'name']);
        });

        Schema::create('product_packagings', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('product_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')
                ->constrained('units_of_measure')->restrictOnDelete();
            $table->string('code', 60);
            $table->string('name', 150);
            $table->string('barcode', 100)->nullable()->unique();
            $table->decimal('conversion_to_base', 18, 6);
            $table->boolean('is_default_sale')->default(false);
            $table->boolean('is_default_purchase')->default(false);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['product_id', 'code']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('price_list_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('product_packaging_id')
                ->constrained()->restrictOnDelete();
            $table->decimal('selling_price', 18, 2);
            $table->decimal('minimum_selling_price', 18, 2)->nullable();
            $table->decimal('maximum_discount_percent', 7, 2)->nullable();
            $table->timestamps();
            $table->unique(['price_list_id', 'product_packaging_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('product_packagings');
        Schema::dropIfExists('products');
    }
};
