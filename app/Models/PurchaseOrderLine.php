<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrderLine extends Model {
    protected $fillable = ['purchase_order_id','line_number','item_id','quantity','received_quantity','invoiced_quantity','uom_id','unit_price','tax_rate','discount_rate','line_total','required_date','expected_date','notes','status'];
    protected $casts = ['quantity' => 'decimal:4','received_quantity' => 'decimal:4','unit_price' => 'decimal:4'];
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function uom() { return $this->belongsTo(Uom::class); }

}