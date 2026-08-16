<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->unique()->after('id');
            $table->string('phone', 40)->nullable()->after('email');
            $table->string('status', 20)->default('active')->index()->after('password');
            $table->foreignId('default_organization_id')
                ->nullable()
                ->after('status')
                ->constrained('organizations')
                ->nullOnDelete();
            $table->boolean('mfa_required')->default(false)->after('default_organization_id');
            $table->timestamp('mfa_confirmed_at')->nullable()->after('mfa_required');
            $table->timestamp('last_login_at')->nullable()->after('mfa_confirmed_at');
            $table->timestamp('suspended_at')->nullable()->after('last_login_at');
        });

        DB::table('users')
            ->whereNull('public_id')
            ->orderBy('id')
            ->eachById(function ($user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['public_id' => (string) Str::ulid()]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('default_organization_id');
            $table->dropColumn([
                'public_id',
                'phone',
                'status',
                'mfa_required',
                'mfa_confirmed_at',
                'last_login_at',
                'suspended_at',
            ]);
        });
    }
};
