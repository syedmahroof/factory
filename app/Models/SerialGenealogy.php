<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SerialGenealogy extends Model {
    protected $guarded = [];
    protected $table = 'serial_genealogies';
    public function productionOrder() { return $this->belongsTo(ProductionOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function parentSerials() { return $this->hasMany(SerialGenealogy::class, 'parent_serial_number', 'child_serial_number'); }
}