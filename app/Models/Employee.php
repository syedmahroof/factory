<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model {
    use SoftDeletes;
    protected $fillable = [
        'user_id','company_id','plant_id','department_id','employee_code','first_name','last_name',
        'email','phone','date_of_birth','gender','address','hire_date','termination_date',
        'employment_type','status','emergency_contact_name','emergency_contact_phone','notes',
    ];
    protected $casts = ['hire_date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function skills() { return $this->belongsToMany(Skill::class, 'employee_skills'); }
    public function attendance() { return $this->hasMany(Attendance::class); }
}