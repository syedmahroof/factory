<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ItemCategory extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','code','name','parent_id','is_active'];
    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }
    public function company() { return $this->belongsTo(Company::class); }
    public function parent() { return $this->belongsTo(ItemCategory::class, 'parent_id'); }
    public function items() { return $this->hasMany(Item::class, 'category_id'); }
}
