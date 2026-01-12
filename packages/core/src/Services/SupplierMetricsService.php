<?php

namespace Lunar\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lunar\Models\Supplier;

class SupplierMetricsService
{
    /**
     * Get performance metrics for all suppliers
     */
    public function getSupplierPerformance(int $days = 30): Collection
    {
        $startDate = now()->subDays($days);

        return Supplier::with(['supplierOrders' => function ($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }])->get()->map(function ($supplier) {
            $orders = $supplier->supplierOrders;

            $delivered = $orders->whereNotNull('delivered_at');
            $cancelled = $orders->whereNotNull('cancelled_at');

            // Calculate average delivery time
            $avgDeliveryTime = $delivered
                ->filter(fn($o) => $o->submitted_at && $o->delivered_at)
                ->map(fn($o) => $o->submitted_at->diffInHours($o->delivered_at))
                ->avg();

            // Calculate profit margins
            $totalRevenue = $orders->sum('order_line_total');
            $totalCost = $orders->sum('estimated_cost_price');
            $profitMargin = $totalRevenue > 0
                ? (($totalRevenue - $totalCost) / $totalRevenue) * 100
                : 0;

            return [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'total_orders' => $orders->count(),
                'delivered_orders' => $delivered->count(),
                'cancelled_orders' => $cancelled->count(),
                'pending_orders' => $orders->where('status', 'pending')->count(),
                'delivery_rate' => $orders->count() > 0
                    ? round(($delivered->count() / $orders->count()) * 100, 2)
                    : 0,
                'cancellation_rate' => $orders->count() > 0
                    ? round(($cancelled->count() / $orders->count()) * 100, 2)
                    : 0,
                'avg_delivery_time_hours' => round($avgDeliveryTime ?? 0, 1),
                'total_revenue_cents' => $totalRevenue,
                'total_cost_cents' => $totalCost,
                'profit_margin_percent' => round($profitMargin, 2),
                'issues_count' => $supplier->supplierOrders()
                    ->whereHas('issues')
                    ->count(),
            ];
        });
    }

    /**
     * Get orders requiring approval
     */
    public function getOrdersRequiringApproval(): Collection
    {
        return DB::table('lunar_supplier_orders')
            ->where('requires_approval', true)
            ->where('artwork_status', 'pending')
            ->whereNull('approved_at')
            ->whereNull('cancelled_at')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get profit margin report
     */
    public function getProfitMarginReport(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $data = DB::table('lunar_supplier_orders')
            ->select([
                DB::raw('SUM(order_line_total) as total_revenue'),
                DB::raw('SUM(estimated_cost_price) as total_cost'),
                DB::raw('SUM(actual_cost_price) as actual_cost'),
                DB::raw('COUNT(*) as order_count'),
            ])
            ->where('created_at', '>=', $startDate)
            ->whereNull('cancelled_at')
            ->first();

        $revenue = $data->total_revenue ?? 0;
        $estimatedCost = $data->estimated_cost_price ?? 0;
        $actualCost = $data->actual_cost ?? 0;

        return [
            'period_days' => $days,
            'total_orders' => $data->order_count ?? 0,
            'total_revenue_cents' => $revenue,
            'estimated_cost_cents' => $estimatedCost,
            'actual_cost_cents' => $actualCost,
            'estimated_profit_cents' => $revenue - $estimatedCost,
            'actual_profit_cents' => $revenue - $actualCost,
            'estimated_margin_percent' => $revenue > 0
                ? round((($revenue - $estimatedCost) / $revenue) * 100, 2)
                : 0,
            'actual_margin_percent' => $revenue > 0 && $actualCost > 0
                ? round((($revenue - $actualCost) / $revenue) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get supplier selection statistics
     */
    public function getSupplierSelectionStats(int $days = 30): Collection
    {
        $startDate = now()->subDays($days);

        return DB::table('lunar_supplier_orders')
            ->select([
                'supplier_id',
                DB::raw('COUNT(*) as times_selected'),
                DB::raw('AVG(JSON_EXTRACT(external_data, "$.selection_score")) as avg_score'),
            ])
            ->where('created_at', '>=', $startDate)
            ->groupBy('supplier_id')
            ->orderByDesc('times_selected')
            ->get()
            ->map(function ($row) {
                $supplier = Supplier::find($row->supplier_id);
                return [
                    'supplier_id' => $row->supplier_id,
                    'supplier_name' => $supplier?->name,
                    'times_selected' => $row->times_selected,
                    'avg_selection_score' => round($row->avg_score ?? 0, 2),
                ];
            });
    }

    /**
     * Get orders by status for dashboard widget
     */
    public function getOrdersByStatus(): array
    {
        $statuses = DB::table('lunar_supplier_orders')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('cancelled_at')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'pending' => $statuses['pending'] ?? 0,
            'submitted' => $statuses['submitted'] ?? 0,
            'processing' => $statuses['processing'] ?? 0,
            'shipped' => $statuses['shipped'] ?? 0,
            'delivered' => $statuses['delivered'] ?? 0,
            'failed' => $statuses['failed'] ?? 0,
        ];
    }
}
