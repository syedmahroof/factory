<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'code', 'name', 'description', 'account_group_id', 'type', 'is_control_account', 'is_bank_account', 'is_cash_account', 'currency', 'opening_balance', 'is_active'];

    protected $casts = ['opening_balance' => 'decimal:4'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accountGroup()
    {
        return $this->belongsTo(AccountGroup::class);
    }

    /*
     * What an account moved is the sum of its journal *lines*. `journals.total_debit`
     * is the whole entry's total across every account it touched, so a trial
     * balance built from it double-counts every line of every multi-account entry.
     */
    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function postedLines()
    {
        return $this->journalLines()->whereHas('journal', fn ($q) => $q->where('status', 'posted'));
    }
}
