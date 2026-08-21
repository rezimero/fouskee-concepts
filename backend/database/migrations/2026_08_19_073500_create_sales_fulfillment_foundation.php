<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->foreignId('fulfilled_by_user_id')->nullable()
                ->after('released_at')
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('fulfilled_at')->nullable()
                ->after('fulfilled_by_user_id');
        });

        Schema::create('sales_fulfillments', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique('sf_public_id_uq');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_location_id')
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->string('fulfillment_number', 80);
            $table->string('status', 30)->default('draft');
            $table->string('pick_idempotency_key', 120);
            $table->char('pick_idempotency_fingerprint', 64);
            $table->foreignId('picked_by_user_id')
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('picked_at');
            $table->string('dispatch_idempotency_key', 120)->nullable();
            $table->foreignId('inventory_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('dispatched_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->string('reversal_idempotency_key', 120)->nullable();
            $table->foreignId('reversal_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->foreignId('reversed_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->unique(
                ['organization_id', 'fulfillment_number'],
                'sf_org_number_uq',
            );
            $table->unique(
                ['organization_id', 'pick_idempotency_key'],
                'sf_org_pick_key_uq',
            );
            $table->unique(
                ['organization_id', 'dispatch_idempotency_key'],
                'sf_org_dispatch_key_uq',
            );
            $table->unique(
                ['organization_id', 'reversal_idempotency_key'],
                'sf_org_reversal_key_uq',
            );
            $table->unique('inventory_movement_id', 'sf_movement_uq');
            $table->unique(
                'reversal_movement_id',
                'sf_reversal_movement_uq',
            );
            $table->index(
                ['organization_id', 'sales_order_id', 'status'],
                'sf_org_order_status_ix',
            );
        });

        Schema::create('sales_fulfillment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_fulfillment_id')
                ->constrained('sales_fulfillments')->restrictOnDelete();
            $table->foreignId('inventory_reservation_id')
                ->constrained('inventory_reservations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()
                ->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('quantity_base_snapshot', 24, 6);
            $table->timestamps();

            $table->unique(
                'inventory_reservation_id',
                'sfl_reservation_uq',
            );
            $table->index(
                ['sales_fulfillment_id', 'product_id'],
                'sfl_fulfillment_product_ix',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_fulfillment_lines');
        Schema::dropIfExists('sales_fulfillments');

        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fulfilled_by_user_id');
            $table->dropColumn('fulfilled_at');
        });
    }
};
