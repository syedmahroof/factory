<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Routing extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','code','name','description','revision','effective_from','effective_to','status','is_active'];
    protected $casts = ['effective_from' => 'date','effective_to' => 'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function operations() { return $this->hasMany(Operation::class)->orderBy('sequence'); }
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