<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','number','item_id','production_version_id','bom_id','routing_id','plant_id',
        'warehouse_id','type','planned_quantity','actual_quantity','scrap_quantity','uom_id','priority',
        'planned_start_date','planned_end_date','actual_start_date','actual_end_date','production_order_id',
        'status','estimated_cost','actual_cost','notes','created_by','approved_by','approved_at',
    ];
    protected $casts = ['planned_start_date' => 'date','planned_end_date' => 'date','planned_quantity' => 'decimal:4'];
    public function company() { return $this->belongsTo(Company::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function operationJobs() { return $this->hasMany(OperationJob::class); }
    public function materialIssues() { return $this->hasMany(MaterialIssue::class); }
    public function outputs() { return $this->hasMany(ProductionOutput::class); }
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