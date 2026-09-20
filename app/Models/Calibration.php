<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Calibration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'asset_code', 'name', 'description', 'manufacturer', 'model',
        'serial_number', 'location_warehouse_id', 'last_calibration_date',
        'next_calibration_date', 'calibration_frequency', 'certificate_number',
        'status', 'notes',
    ];

    protected $casts = [
        'last_calibration_date' => 'date',
        'next_calibration_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'location_warehouse_id');
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
