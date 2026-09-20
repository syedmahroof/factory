<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    protected $fillable = [
        'company_id', 'plant_id', 'warehouse_id', 'bin_id', 'item_id', 'lot_id', 'serial_id',
        'stock_status_id', 'quantity', 'reserved_quantity', 'available_quantity', 'unit_cost', 'total_value',
        'manufacture_date', 'expiry_date', 'retest_date',
    ];

    protected $casts = ['quantity' => 'decimal:4', 'reserved_quantity' => 'decimal:4', 'available_quantity' => 'decimal:4', 'unit_cost' => 'decimal:4'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function bin()
    {
        return $this->belongsTo(Bin::class);
    }

    public function stockStatus()
    {
        return $this->belongsTo(StockStatus::class);
    }
}
