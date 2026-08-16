<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('code', 50)->unique();
            $table->string('legal_name', 160);
            $table->string('trading_name', 160)->nullable();
            $table->char('country_code', 2)->default('NG');
            $table->char('currency_code', 3)->default('NGN');
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
