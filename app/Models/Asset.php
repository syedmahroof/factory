<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','plant_id','code','name','description','type','parent_id','work_center_id',
        'manufacturer','model','serial_number','purchase_date','installation_date','purchase_cost',
        'current_value','warranty_expiry','location_warehouse_id','criticality','status','notes',
    ];
    protected $casts = ['purchase_cost' => 'decimal:4','current_value' => 'decimal:4','purchase_date' => 'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function parent() { return $this->belongsTo(Asset::class, 'parent_id'); }
    public function children() { return $this->hasMany(Asset::class, 'parent_id'); }
    public function maintenancePlans() { return $this->hasMany(MaintenancePlan::class); }
    public function maintenanceOrders() { return $this->hasMany(MaintenanceOrder::class); }
    public function documents() { return $this->hasMany(AssetDocument::class); }
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