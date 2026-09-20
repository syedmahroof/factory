<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RfqResponse extends Model {
    protected $guarded = [];
    public function rfq() { return $this->belongsTo(RequestForQuotation::class, 'rfq_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
}