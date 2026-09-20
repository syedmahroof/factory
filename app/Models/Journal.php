<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journal extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','number','fiscal_period_id','date','type','description','total_debit','total_credit','source_type','source_id','status','created_by','approved_by','approved_at','posted_by','posted_at','reversal_reason'];
    protected $casts = ['date' => 'date','total_debit' => 'decimal:4','total_credit' => 'decimal:4','approved_at' => 'datetime','posted_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class); }
    public function fiscalPeriod() { return $this->belongsTo(FiscalPeriod::class); }
    public function lines() { return $this->hasMany(JournalLine::class); }

}