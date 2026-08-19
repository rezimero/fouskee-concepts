<?php

namespace App\Services\Sales;

use App\Enums\SalesOrderStatus;
use App\Enums\SalesQuotationStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class SalesQuotationConverter
{
    public function __construct(
        private readonly SalesReferenceNumber $references,
        private readonly AuditRecorder $audit,
    ) {}

    public function convert(
        Organization $organization,
        User $actor,
        string $publicId,
    ): SalesOrder {
        return DB::transaction(function () use ($organization, $actor, $publicId) {
            $quotation = SalesQuotation::query()
                ->with('lines')
                ->where('organization_id', $organization->id)
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = SalesOrder::query()
                ->where('source_quotation_id', $quotation->id)
                ->first();

            if ($existing !== null) {
                return $existing->load($this->relations());
            }

            if ($quotation->status !== SalesQuotationStatus::Accepted) {
                throw new InventoryDomainException(
                    'sales_quotation_not_accepted',
                    'Only an accepted quotation can become an order.',
                    409,
                );
            }

            $order = SalesOrder::query()->create([
                'organization_id' => $organization->id,
                'sales_order_number' => $this->references->order($organization),
                'customer_id' => $quotation->customer_id,
                'price_list_id' => $quotation->price_list_id,
                'source_quotation_id' => $quotation->id,
                'status' => SalesOrderStatus::Draft,
                'settlement_type' => $quotation->settlement_type,
                'currency_code' => $quotation->currency_code,
                'subtotal_amount' => $quotation->subtotal_amount,
                'total_amount' => $quotation->total_amount,
                'notes' => $quotation->notes,
                'idempotency_key' => 'quote:'.$quotation->public_id,
                'idempotency_fingerprint' => hash('sha256', $quotation->public_id),
                'created_by_user_id' => $actor->id,
            ]);

            foreach ($quotation->lines as $line) {
                $order->lines()->create($this->orderLine($line->toArray()));
            }

            $quotation->update([
                'status' => SalesQuotationStatus::Converted,
                'converted_by_user_id' => $actor->id,
                'converted_at' => now(),
            ]);

            $this->audit->record(
                event: 'sales.quotation.converted',
                actor: $actor,
                subject: $quotation,
                organizationId: $organization->id,
                after: ['sales_order_public_id' => $order->public_id],
            );

            return $order->load($this->relations());
        }, 3);
    }

    private function orderLine(array $line): array
    {
        return [
            'line_number' => $line['line_number'],
            'product_id' => $line['product_id'],
            'product_packaging_id' => $line['product_packaging_id'],
            'product_sku_snapshot' => $line['product_sku_snapshot'],
            'product_name_snapshot' => $line['product_name_snapshot'],
            'packaging_name_snapshot' => $line['packaging_name_snapshot'],
            'conversion_to_base_snapshot' => $line['conversion_to_base_snapshot'],
            'ordered_quantity' => $line['quoted_quantity'],
            'ordered_quantity_base' => $line['quoted_quantity_base'],
            'list_unit_price' => $line['list_unit_price'],
            'floor_unit_price' => $line['floor_unit_price'],
            'effective_unit_price' => $line['effective_unit_price'],
            'line_total' => $line['line_total'],
            'price_exception' => $line['price_exception'],
        ];
    }

    private function relations(): array
    {
        return ['customer', 'priceList', 'lines.product', 'lines.packaging'];
    }
}
