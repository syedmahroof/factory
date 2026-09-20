<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','number','type','supplier_id','rfq_id','purchase_requisition_id','created_by',
        'plant_id','warehouse_id','order_date','expected_delivery_date','payment_terms','currency',
        'subtotal','tax_amount','shipping_cost','total_amount','terms_and_conditions','notes',
        'status','approved_by','approved_at',
    ];
    protected $casts = ['order_date' => 'date','subtotal' => 'decimal:4','tax_amount' => 'decimal:4','total_amount' => 'decimal:4','approved_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function lines() { return $this->hasMany(PurchaseOrderLine::class); }
    public function goodsReceipts() { return $this->hasMany(GoodsReceipt::class); }

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