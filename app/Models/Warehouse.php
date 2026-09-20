<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'plant_id', 'code', 'name', 'address', 'is_quarantine', 'is_active'];

    public function company() { return $this->belongsTo(Company::class); }
    public function plant() { return $this->belongsTo(Plant::class); }
    public function zones() { return $this->hasMany(Zone::class); }
    public function stockBalances() { return $this->hasMany(StockBalance::class); }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }
}