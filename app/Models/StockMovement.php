<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockMovement extends Model
{
    protected $fillable = [
        'company_id', 'document_type', 'document_number', 'item_id', 'from_warehouse_id', 'from_bin_id',
        'to_warehouse_id', 'to_bin_id', 'quantity', 'uom_id', 'unit_cost', 'total_cost', 'lot_id', 'serial_id',
        'stock_status_id', 'reference_type', 'reference_id', 'created_by', 'notes',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /*
     * `uuid` is a column beside an auto-increment `id`, not the key itself, so it
     * is filled the way every other model here fills it. The class used to reach
     * for a `HasUuids` trait it never imported, which made every read of this
     * table a fatal error.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }
}
