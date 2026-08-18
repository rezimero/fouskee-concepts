<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_count_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('count_number', 80);
            $table->foreignId('inventory_location_id')
                ->constrained('inventory_locations')->restrictOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->string('reason_code', 60);
            $table->text('notes')->nullable();
            $table->decimal('total_abs_variance_value', 24, 6)->default(0);
            $table->string('submission_idempotency_key', 120)->nullable();
            $table->string('approval_idempotency_key', 120)->nullable();
            $table->foreignId('approval_request_id')->nullable()
                ->constrained('approval_requests')->restrictOnDelete();
            $table->foreignId('created_by_user_id')
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_movement_id')->nullable()
                ->constrained('inventory_movements')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'count_number']);
            $table->unique(
                ['organization_id', 'submission_idempotency_key'],
                'stock_count_submission_key_unique',
            );
        });

        Schema::create('stock_count_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_count_session_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()
                ->constrained('inventory_lots')->restrictOnDelete();
            $table->decimal('system_quantity_base', 24, 6);
            $table->decimal('counted_quantity_base', 24, 6);
            $table->decimal('variance_quantity_base', 24, 6);
            $table->decimal('unit_cost_snapshot', 24, 6);
            $table->decimal('variance_value', 24, 6);
            $table->unsignedBigInteger('balance_version_snapshot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_count_sessions');
    }
};
