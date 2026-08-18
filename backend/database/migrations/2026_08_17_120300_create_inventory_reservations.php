<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_location_id')
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()
                ->constrained('inventory_lots')->restrictOnDelete();
            $table->string('source_reference_type', 80)->nullable();
            $table->string('source_reference_public_id', 64)->nullable();
            $table->decimal('quantity_base', 24, 6);
            $table->string('status', 30)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('released_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(
                ['organization_id', 'inventory_location_id', 'product_id', 'status'],
                'inv_res_org_loc_product_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
