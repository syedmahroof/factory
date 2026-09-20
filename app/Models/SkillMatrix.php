<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SkillMatrix extends Model {
    protected $guarded = [];
    protected $table = 'skill_matrix';
    protected $casts = ['certification_date' => 'date', 'certification_expiry' => 'date'];
    public function employee() { return $this->belongsTo(Employee::class); }
}