<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalesOrderLine extends Model {
    protected $fillable = ['sales_order_id','line_number','item_id','quantity','picked_quantity','shipped_quantity','invoiced_quantity','uom_id','unit_price','discount_rate','tax_rate','line_total','requested_date','confirmed_date','notes','status'];
    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }

}