<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ShipmentLine extends Model {
    protected $fillable = ['shipment_id','sales_order_line_id','item_id','quantity','lot_id','serial_id'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function item() { return $this->belongsTo(Item::class); }

}