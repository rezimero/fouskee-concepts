<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE product_packagings '
            .'ADD default_sale_guard BIGINT UNSIGNED '
            .'GENERATED ALWAYS AS (IF(is_default_sale, product_id, NULL)) STORED, '
            .'ADD UNIQUE INDEX product_packagings_one_default_sale (default_sale_guard)',
        );

        DB::statement(
            'ALTER TABLE product_packagings '
            .'ADD default_purchase_guard BIGINT UNSIGNED '
            .'GENERATED ALWAYS AS (IF(is_default_purchase, product_id, NULL)) STORED, '
            .'ADD UNIQUE INDEX product_packagings_one_default_purchase (default_purchase_guard)',
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE product_packagings '
            .'DROP INDEX product_packagings_one_default_purchase, '
            .'DROP COLUMN default_purchase_guard',
        );

        DB::statement(
            'ALTER TABLE product_packagings '
            .'DROP INDEX product_packagings_one_default_sale, '
            .'DROP COLUMN default_sale_guard',
        );
    }
};
