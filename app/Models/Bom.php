<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bom extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','item_id','routing_id','revision','type','scrap_factor','effective_from','effective_to','status','is_active'];
    protected $casts = ['effective_from' => 'date','effective_to' => 'date','scrap_factor' => 'decimal:2'];
    public function company() { return $this->belongsTo(Company::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function routing() { return $this->belongsTo(Routing::class); }
    public function lines() { return $this->hasMany(BomLine::class)->orderBy('sequence'); }

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