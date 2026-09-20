<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReturnMerchandiseLine extends Model {
    protected $guarded = [];
    protected $table = 'return_merchandise_lines';
    public function rma() { return $this->belongsTo(ReturnMerchandiseAuthorization::class, 'rma_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}