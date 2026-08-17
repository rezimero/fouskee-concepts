<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')
                ->constrained()->restrictOnDelete();
            $table->string('customer_number', 60);
            $table->string('segment', 30);
            $table->string('display_name', 200);
            $table->string('legal_name', 200)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('tax_identifier', 100)->nullable();
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->foreignId('default_price_list_id')->nullable()
                ->constrained('price_lists')->restrictOnDelete();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'customer_number']);
            $table->index(['organization_id', 'status', 'display_name']);
        });

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')
                ->constrained()->restrictOnDelete();
            $table->string('supplier_number', 60);
            $table->string('display_name', 200);
            $table->string('legal_name', 200)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('tax_identifier', 100)->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'supplier_number']);
            $table->index(['organization_id', 'status', 'display_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
