<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * The table is `inspections`; the class the controllers reach for has always been
 * QualityInspection. Naming the table explicitly is cheaper than renaming either.
 */
class QualityInspection extends Model
{
    use SoftDeletes;

    protected $table = 'inspections';

    protected $fillable = [
        'company_id', 'number', 'quality_plan_id', 'source_type', 'source_id', 'item_id',
        'lot_id', 'serial_id', 'quantity_inspected', 'quantity_accepted', 'quantity_rejected',
        'quantity_on_hold', 'inspector_id', 'inspection_date', 'result', 'notes', 'status',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'quantity_inspected' => 'decimal:4',
        'quantity_accepted' => 'decimal:4',
        'quantity_rejected' => 'decimal:4',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function qualityPlan()
    {
        return $this->belongsTo(QualityPlan::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
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
