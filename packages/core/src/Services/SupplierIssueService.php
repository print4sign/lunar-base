<?php

namespace Lunar\Services;

use Lunar\Base\LunarUser;
use Lunar\Models\Contracts\SupplierOrder;
use Lunar\Models\SupplierIssue;

class SupplierIssueService
{
    public function reportIssue(
        SupplierOrder $supplierOrder,
        LunarUser $user,
        string $type,
        string $description,
        string $severity = 'medium',
        array $images = []
    ): SupplierIssue {

        $issue = SupplierIssue::create([
            'supplier_order_id' => $supplierOrder->id,
            'type' => $type,
            'severity' => $severity,
            'description' => $description,
            'images' => $images,
            'status' => 'reported',
            'reported_by' => $user->id,
            'reported_at' => now(),
        ]);

        // Log activity
        activity('supplier_issues')
            ->performedOn($issue)
            ->causedBy($user)
            ->withProperties([
                'type' => $type,
                'severity' => $severity,
            ])
            ->log('Issue reported');

        return $issue;
    }

    public function acknowledgeIssue(SupplierIssue $issue, LunarUser $user): void
    {
        $issue->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        activity('supplier_issues')
            ->performedOn($issue)
            ->causedBy($user)
            ->log('Issue acknowledged');
    }

    public function resolveIssue(
        SupplierIssue $issue,
        LunarUser $user,
        string $resolution,
        ?int $compensationAmount = null,
        ?string $customerNotes = null
    ): void {

        $issue->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'compensation_amount' => $compensationAmount,
            'customer_notes' => $customerNotes,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        activity('supplier_issues')
            ->performedOn($issue)
            ->causedBy($user)
            ->withProperties([
                'resolution' => $resolution,
                'compensation' => $compensationAmount,
            ])
            ->log('Issue resolved');
    }

    public function requestReprint(SupplierIssue $issue): SupplierOrder
    {
        $originalOrder = $issue->supplierOrder;

        // Create new supplier order for reprint
        $reprintOrder = SupplierOrder::create([
            'order_id' => $originalOrder->order_id,
            'order_line_id' => $originalOrder->order_line_id,
            'supplier_id' => $originalOrder->supplier_id,
            'supplier_product_id' => $originalOrder->supplier_product_id,
            'status' => SupplierOrder::STATUS_PENDING,
            'estimated_cost_price' => 0, // Reprint at no cost
            'order_line_unit_price' => 0,
            'order_line_total' => 0,
            'external_data' => [
                'reprint_for_issue' => $issue->id,
                'original_supplier_order' => $originalOrder->id,
            ],
        ]);

        activity('supplier_orders')
            ->performedOn($reprintOrder)
            ->withProperties([
                'reason' => 'reprint_for_issue',
                'issue_id' => $issue->id,
            ])
            ->log('Reprint order created');

        return $reprintOrder;
    }
}
