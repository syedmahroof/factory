<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Zone extends Model
{
    use SoftDeletes;

    protected $fillable = ['warehouse_id', 'code', 'name', 'is_active'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function bins() { return $this->hasMany(Bin::class); }
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