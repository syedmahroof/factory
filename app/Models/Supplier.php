<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','code','name','legal_name','address','city','state','country','postal_code',
        'phone','email','website','tax_id','payment_terms','credit_limit','outstanding_balance',
        'currency','rating','status','qualification_expiry',
    ];
    protected $casts = ['credit_limit' => 'decimal:4','rating' => 'decimal:1'];
    public function company() { return $this->belongsTo(Company::class); }
    public function certifications() { return $this->hasMany(SupplierCertification::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
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