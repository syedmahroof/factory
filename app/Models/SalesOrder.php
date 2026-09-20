<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model {
    use SoftDeletes;
    protected $fillable = [
        'company_id','number','customer_id','quotation_id','created_by','plant_id','warehouse_id',
        'order_date','requested_delivery_date','confirmed_delivery_date','po_number','currency',
        'payment_terms','subtotal','tax_amount','discount_amount','shipping_cost','total_amount',
        'shipping_address','notes','priority','status','approved_by','approved_at',
    ];
    protected $casts = ['order_date' => 'date','subtotal' => 'decimal:4','total_amount' => 'decimal:4'];
    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function lines() { return $this->hasMany(SalesOrderLine::class); }
    public function shipments() { return $this->hasMany(Shipment::class); }

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