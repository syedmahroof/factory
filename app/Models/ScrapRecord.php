<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ScrapRecord extends Model {
    protected $guarded = [];
    protected $table = 'scrap_records';
    public function productionOrder() { return $this->belongsTo(ProductionOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
}