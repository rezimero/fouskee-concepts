<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_transaction_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_transaction_id')
                ->constrained('pos_transactions')
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();
            $table->decimal('quantity', 24, 6);
            $table->decimal('unit_price', 24, 6);
            $table->decimal('line_total', 24, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_lines');
    }
};
