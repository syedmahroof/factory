<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Attendance extends Model {
    protected $fillable = ['employee_id','date','shift_id','clock_in','clock_out','hours_worked','overtime_hours','status','source','notes','approved_by'];
    protected $casts = ['date' => 'date','clock_in' => 'datetime','clock_out' => 'datetime'];
    public function employee() { return $this->belongsTo(Employee::class); }

}