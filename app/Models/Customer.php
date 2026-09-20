<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','code','name','legal_name','address','city','state','country','postal_code',
        'phone','email','website','tax_id','credit_terms','credit_limit','outstanding_balance',
        'currency','sales_rep_id','status',
    ];
    protected $casts = ['credit_limit' => 'decimal:4','outstanding_balance' => 'decimal:4'];
    public function company() { return $this->belongsTo(Company::class); }
    public function salesRep() { return $this->belongsTo(User::class, 'sales_rep_id'); }
    public function quotations() { return $this->hasMany(Quotation::class); }
    public function salesOrders() { return $this->hasMany(SalesOrder::class); }
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