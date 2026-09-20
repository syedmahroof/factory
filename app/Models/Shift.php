<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','code','name','start_time','end_time','hours','is_active'];
    protected $casts = ['start_time' => 'datetime:H:i','end_time' => 'datetime:H:i','hours' => 'decimal:2'];
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