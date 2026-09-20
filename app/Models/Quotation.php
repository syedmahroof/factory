<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','customer_id','created_by','quotation_date','valid_until','currency','subtotal','tax_amount','discount_amount','total_amount','terms_and_conditions','notes','status','revision'];
    protected $casts = ['quotation_date' => 'date','valid_until' => 'date','subtotal' => 'decimal:4'];
    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function lines() { return $this->hasMany(QuotationLine::class); }
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