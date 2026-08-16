<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_events')) {
            return;
        }

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('impersonated_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 120)->index();
            $table->string('subject_type', 160)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->text('reason')->nullable();
            $table->string('request_id', 40)->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('route_name', 160)->nullable();
            $table->string('method', 10)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at', 6)->index();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS audit_events_prevent_update');
        DB::unprepared('DROP TRIGGER IF EXISTS audit_events_prevent_delete');
        Schema::dropIfExists('audit_events');
    }
};
