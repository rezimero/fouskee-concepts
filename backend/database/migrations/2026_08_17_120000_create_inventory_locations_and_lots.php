<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('code', 60);
            $table->string('name', 160);
            $table->string('type', 30)->default('warehouse');
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status', 'name']);
        });

        Schema::create('inventory_lots', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('code', 100);
            $table->timestamp('received_at')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'product_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('inventory_locations');
    }
};
