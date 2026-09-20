<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class TaxRule extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','code','name','type','rate','account_id','effective_from','effective_to','is_active'];
    protected $casts = ['rate' => 'decimal:2','effective_from' => 'date','effective_to' => 'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function account() { return $this->belongsTo(Account::class); }

}