<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BankAccount extends Model {
    protected $guarded = [];
    protected $table = 'bank_accounts';
    public function account() { return $this->belongsTo(Account::class); }
    public function reconciliations() { return $this->hasMany(BankReconciliation::class); }
}