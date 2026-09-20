<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ThreeWayMatch extends Model {
    protected $guarded = [];
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function goodsReceipt() { return $this->belongsTo(GoodsReceipt::class); }
}