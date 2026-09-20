<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RfqLine extends Model {
    protected $guarded = [];
    public function rfq() { return $this->belongsTo(RequestForQuotation::class, 'rfq_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}