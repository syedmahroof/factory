<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class PayrollRun extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    protected $table = 'payroll_runs';
    public function payslips() { return $this->hasMany(Payslip::class); }
    public function preparedBy() { return $this->belongsTo(User::class, 'prepared_by'); }
}