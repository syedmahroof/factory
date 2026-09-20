<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_id', 'lot_number', 'manufacture_date', 'expiry_date', 'retest_date',
        'initial_quantity', 'current_quantity', 'stock_status_id',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'retest_date' => 'date',
        'initial_quantity' => 'decimal:4',
        'current_quantity' => 'decimal:4',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function stockStatus()
    {
        return $this->belongsTo(StockStatus::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

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
