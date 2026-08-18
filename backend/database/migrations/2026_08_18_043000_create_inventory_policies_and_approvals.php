<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->unique()
                ->constrained()->restrictOnDelete();
            $table->decimal(
                'count_variance_approval_value_threshold',
                24,
                6,
            )->default(100000);
            $table->unsignedSmallInteger('backdate_window_days')->default(2);
            $table->boolean('require_separate_transfer_receiver')->default(true);
            $table->boolean('allow_immediate_transfers')->default(true);
            $table->timestamps();
        });

        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('subject_type', 100);
            $table->string('subject_public_id', 64);
            $table->string('action', 100);
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('requested_by_user_id')
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by_user_id')->nullable()
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(
                ['organization_id', 'subject_type', 'subject_public_id'],
                'approval_subject_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('inventory_policies');
    }
};
