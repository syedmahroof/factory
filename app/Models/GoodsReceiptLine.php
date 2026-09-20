<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GoodsReceiptLine extends Model {
    protected $fillable = ['goods_receipt_id','purchase_order_line_id','item_id','quantity','accepted_quantity','rejected_quantity','uom_id','lot_id','stock_status_id','bin_id','notes'];
    public function goodsReceipt() { return $this->belongsTo(GoodsReceipt::class); }
    public function item() { return $this->belongsTo(Item::class); }

}