<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PeriodClose extends Model {
    protected $guarded = [];
    protected $table = 'period_closes';
    protected $casts = ['closed_at' => 'datetime'];
    public function fiscalPeriod() { return $this->belongsTo(FiscalPeriod::class); }
    public function closedBy() { return $this->belongsTo(User::class, 'closed_by'); }
}