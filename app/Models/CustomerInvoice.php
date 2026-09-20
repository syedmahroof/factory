<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInvoice extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','customer_id','sales_order_id','shipment_id','invoice_date','due_date','currency','subtotal','tax_amount','total_amount','amount_paid','balance_due','status','journal_id','notes'];
    protected $casts = ['invoice_date' => 'date','due_date' => 'date','subtotal' => 'decimal:4','total_amount' => 'decimal:4'];
    public function customer() { return $this->belongsTo(Customer::class); }
}