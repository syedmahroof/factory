<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityPlan extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','code','name','description','type','item_id','supplier_id','operation_id','frequency_days','sample_percentage','min_sample_size','is_active'];
    public function company() { return $this->belongsTo(Company::class); }
    public function characteristics() { return $this->hasMany(QualityCharacteristic::class); }
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