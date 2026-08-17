<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_measure', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->nullable()
                ->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('symbol', 20);
            $table->string('kind', 30);
            $table->unsignedTinyInteger('decimal_places')->default(3);
            $table->boolean('is_system')->default(false);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
            $table->index(['status', 'kind']);
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')
                ->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')
                ->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('price_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')
                ->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->char('currency_code', 3)->default('NGN');
            $table->string('status', 30)->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('units_of_measure');
    }
};
