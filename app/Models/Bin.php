<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Bin extends Model
{
    use SoftDeletes;

    protected $fillable = ['zone_id', 'code', 'name', 'max_capacity', 'uom_id', 'is_active'];

    protected $casts = ['max_capacity' => 'decimal:4', 'is_active' => 'boolean'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
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
