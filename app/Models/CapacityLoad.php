<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CapacityLoad extends Model {
    protected $guarded = [];
    protected $casts = ['load_date' => 'date'];
    public function workCenter() { return $this->belongsTo(WorkCenter::class); }
}