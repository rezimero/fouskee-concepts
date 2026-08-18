<?php

namespace App\Services\Procurement;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Services\Support\DecimalMath;

class SupplierInvoiceLineFactory
{
    public function __construct(
        private readonly ProcurementProductResolver $products,
        private readonly DecimalMath $math,
    ) {}

    public function make(
        Organization $organization,
        Supplier $supplier,
        array $input,
        int $lineNumber,
    ): array {
        $product = $this->products->product(
            $organization,
            $input['product_id'],
        );
        $orderLine = $this->orderLine(
            $organization,
            $supplier,
            $product->id,
            $input['purchase_order_line_id'] ?? null,
        );

        $packaging = $this->products->packaging(
            $product,
            $input['packaging_id'] ?? null,
        );

        $packagingId = $packaging?->id ?? $orderLine?->product_packaging_id;
        $conversion = (string) (
            $packaging?->conversion_to_base
            ?? $orderLine?->conversion_to_base_snapshot
            ?? 1
        );
        $packagingName = $packaging?->name
            ?? $orderLine?->packaging_name_snapshot;

        $quantity = (string) $input['quantity'];
        $unitPrice = (string) $input['unit_price'];

        return [
            'line_number' => $lineNumber,
            'purchase_order_line_id' => $orderLine?->id,
            'product_id' => $product->id,
            'product_packaging_id' => $packagingId,
            'product_sku_snapshot' => $product->sku,
            'product_name_snapshot' => $product->name,
            'packaging_name_snapshot' => $packagingName,
            'conversion_to_base_snapshot' => $conversion,
            'invoiced_quantity' => $quantity,
            'invoiced_quantity_base' => $this->math->multiply(
                $quantity,
                $conversion,
            ),
            'unit_price' => $unitPrice,
            'unit_price_base' => $this->math->divide(
                $unitPrice,
                $conversion,
            ),
            'line_amount' => $this->math->multiply($quantity, $unitPrice),
        ];
    }

    private function orderLine(
        Organization $organization,
        Supplier $supplier,
        int $productId,
        ?string $publicId,
    ): ?PurchaseOrderLine {
        if ($publicId === null) {
            return null;
        }

        $line = PurchaseOrderLine::query()
            ->where('public_id', $publicId)
            ->where('product_id', $productId)
            ->whereHas(
                'purchaseOrder',
                fn ($query) => $query
                    ->where('organization_id', $organization->id)
                    ->where('supplier_id', $supplier->id)
                    ->whereIn('status', [
                        'approved',
                        'partially_received',
                        'received',
                        'closed',
                    ]),
            )
            ->first();

        if ($line === null) {
            throw new InventoryDomainException(
                'procurement_invoice_po_line_invalid',
                'The linked purchase-order line does not match this supplier and product.',
                422,
            );
        }

        return $line;
    }
}
