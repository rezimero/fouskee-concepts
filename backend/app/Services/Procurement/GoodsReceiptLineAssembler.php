<?php

namespace App\Services\Procurement;

use App\Exceptions\InventoryDomainException;
use App\Models\PurchaseOrder;

class GoodsReceiptLineAssembler
{
    public function __construct(
        private readonly GoodsReceiptLineFactory $factory,
    ) {}

    public function make(PurchaseOrder $order, array $inputs): array
    {
        $lines = [];
        $seen = [];

        foreach ($inputs as $input) {
            $line = $this->factory->make($order, $input);
            $id = $line['purchase_order_line_id'];

            if (isset($seen[$id])) {
                throw new InventoryDomainException(
                    'procurement_duplicate_receipt_line',
                    'A purchase-order line may appear only once per receipt.',
                    422,
                );
            }

            $seen[$id] = true;
            $lines[] = $line;
        }

        return $lines;
    }
}
