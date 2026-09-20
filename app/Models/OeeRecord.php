<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OeeRecord extends Model {
    protected $guarded = [];
    protected $table = 'oee_records';
    protected $casts = ['record_date' => 'date'];
    public function workCenter() { return $this->belongsTo(WorkCenter::class); }
}