<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BankReconciliation extends Model {
    protected $guarded = [];
    protected $table = 'bank_reconciliations';
    protected $casts = ['statement_date' => 'date'];
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
}