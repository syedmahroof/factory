<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Uom extends Model {
    use SoftDeletes;
    protected $table = 'uoms';
    protected $fillable = ['company_id','code','name','type','base_conversion','is_active'];
    protected $casts = ['base_conversion' => 'decimal:6','is_active' => 'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
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