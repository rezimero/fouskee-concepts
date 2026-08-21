<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->string('transaction_number', 80)->unique();
            $table->decimal('total_amount', 24, 6)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('pos_transactions');
    }
};
