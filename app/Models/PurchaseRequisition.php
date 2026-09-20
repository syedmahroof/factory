<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequisition extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','source','requested_by','department_id','required_date','justification','status','approved_by','approved_at'];
    protected $casts = ['required_date' => 'date','approved_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function lines() { return $this->hasMany(PurchaseRequisitionLine::class); }
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