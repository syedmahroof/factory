<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','sales_order_id','customer_id','created_by','invoice_date','due_date','currency','subtotal','tax_amount','total_amount','amount_paid','balance_due','notes','status'];
    protected $casts = ['invoice_date' => 'date','due_date' => 'date','subtotal' => 'decimal:4','total_amount' => 'decimal:4'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }

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