<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuotationLine extends Model {
    protected $fillable = ['quotation_id','line_number','item_id','quantity','uom_id','unit_price','discount_rate','tax_rate','line_total','notes'];
    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function item() { return $this->belongsTo(Item::class); }

}