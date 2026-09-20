<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends BaseController
{
    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer']);
        $this->applyScopes($query, $request);
        $request->merge(['search_fields' => ['number', 'status']]);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'payment_terms' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // ATP Check: verify stock availability
        $atpIssues = $this->checkAtp($validated['lines']);
        if (! empty($atpIssues)) {
            return $this->error('Stock availability issues', 422, $atpIssues);
        }

        // Check credit limit
        $customer = Customer::find($validated['customer_id']);
        if ($customer && $customer->credit_limit > 0) {
            $totalAmount = collect($validated['lines'])->sum(fn ($l) => $l['quantity'] * $l['unit_price']);
            $outstanding = SalesOrder::where('customer_id', $customer->id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->sum('total_amount');

            if (($outstanding + $totalAmount) > $customer->credit_limit) {
                return $this->error("Credit limit exceeded. Limit: {$customer->credit_limit}, Outstanding: {$outstanding}, Order: {$totalAmount}", 422);
            }
        }

        $order = DB::transaction(function () use ($validated) {
            $totalAmount = 0;
            foreach ($validated['lines'] as $line) {
                $totalAmount += $line['quantity'] * $line['unit_price'] * (1 + ($line['tax_rate'] ?? 0) / 100);
            }

            $number = 'SO-'.str_pad(SalesOrder::max('id') + 1, 6, '0', STR_PAD_LEFT);

            $order = SalesOrder::create([
                'number' => $number,
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? 'net_30',
                'shipping_address' => $validated['shipping_address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'company_id' => auth()->user()->company_id,
            ]);

            foreach ($validated['lines'] as $line) {
                SalesOrderLine::create([
                    'sales_order_id' => $order->id,
                    'item_id' => $line['item_id'],
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_rate' => $line['tax_rate'] ?? 0,
                    'total' => $line['quantity'] * $line['unit_price'] * (1 + ($line['tax_rate'] ?? 0) / 100),
                ]);
            }

            return $order;
        });

        return $this->success($order->load('lines.item', 'customer'), 'Sales order created', 201);
    }

    public function show(SalesOrder $salesOrder)
    {
        return $this->success($salesOrder->load('lines.item', 'customer'));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if (! in_array($salesOrder->status, ['draft'])) {
            return $this->error('Only draft orders can be edited', 422);
        }

        $validated = $request->validate([
            'delivery_date' => 'nullable|date',
            'payment_terms' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $salesOrder->update($validated);

        return $this->success($salesOrder, 'Sales order updated');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if (! in_array($salesOrder->status, ['draft', 'cancelled'])) {
            return $this->error('Cannot delete confirmed/in-progress orders', 422);
        }

        $salesOrder->lines()->delete();
        $salesOrder->delete();

        return $this->success(null, 'Sales order deleted');
    }

    /**
     * Confirm a sales order — reserves stock.
     */
    public function confirm(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return $this->error('Only draft orders can be confirmed', 422);
        }

        // Reserve stock for each line
        foreach ($salesOrder->lines as $line) {
            $balance = StockBalance::where('item_id', $line->item_id)
                ->where('company_id', $salesOrder->company_id)
                ->lockForUpdate()
                ->first();

            if (! $balance || $balance->quantity_available < $line->quantity) {
                $avail = $balance ? $balance->quantity_available : 0;

                return $this->error("Insufficient stock for item {$line->item->name}. Available: {$avail}, Required: {$line->quantity}", 422);
            }

            $balance->quantity_reserved += $line->quantity;
            $balance->quantity_available -= $line->quantity;
            $balance->save();
        }

        $salesOrder->update(['status' => 'confirmed']);

        return $this->success($salesOrder, 'Sales order confirmed');
    }

    /**
     * ATP (Available to Promise) check.
     * Returns items where stock is insufficient.
     */
    protected function checkAtp(array $lines): array
    {
        $issues = [];

        foreach ($lines as $idx => $line) {
            $totalAvailable = StockBalance::where('item_id', $line['item_id'])
                ->sum('quantity_available');

            if ($totalAvailable < $line['quantity']) {
                $issues[] = [
                    'line' => $idx + 1,
                    'item_id' => $line['item_id'],
                    'required' => $line['quantity'],
                    'available' => $totalAvailable,
                    'shortage' => $line['quantity'] - $totalAvailable,
                    'message' => "Insufficient stock. Required: {$line['quantity']}, Available: {$totalAvailable}",
                ];
            }
        }

        return $issues;
    }
}
