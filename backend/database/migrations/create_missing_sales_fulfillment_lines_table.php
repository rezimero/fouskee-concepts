<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_fulfillment_lines')) {
            return;
        }

        Schema::create('sales_fulfillment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_fulfillment_id')->constrained('sales_fulfillments')->restrictOnDelete();
            $table->foreignId('inventory_reservation_id')->constrained('inventory_reservations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('quantity_base_snapshot', 24, 6);
            $table->timestamps();
            $table->unique('inventory_reservation_id', 'sfl_reservation_uq');
            $table->index(['sales_fulfillment_id', 'product_id'], 'sfl_fulfillment_product_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_fulfillment_lines');
    }
};
