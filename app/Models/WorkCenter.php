<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class WorkCenter extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','plant_id','code','name','description','capacity_per_hour','capacity_per_day',
        'cost_per_hour','setup_cost','cost_center_id','type','is_bottleneck','is_active',
    ];
    protected $casts = ['capacity_per_hour' => 'decimal:4','cost_per_hour' => 'decimal:4'];
    public function company() { return $this->belongsTo(Company::class); }
    public function plant() { return $this->belongsTo(Plant::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
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