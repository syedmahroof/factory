<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WipBalance extends Model {
    protected $guarded = [];
    protected $table = 'wip_balances';
    public function productionOrder() { return $this->belongsTo(ProductionOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function workCenter() { return $this->belongsTo(WorkCenter::class); }
}