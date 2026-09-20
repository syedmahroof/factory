<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ShiftRoster extends Model {
    protected $guarded = [];
    protected $table = 'shift_rosters';
    protected $casts = ['roster_date' => 'date'];
    public function shift() { return $this->belongsTo(Shift::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
}