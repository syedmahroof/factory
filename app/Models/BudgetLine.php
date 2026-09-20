<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BudgetLine extends Model {
    protected $guarded = [];
    protected $table = 'budget_lines';
    public function budget() { return $this->belongsTo(Budget::class); }
    public function account() { return $this->belongsTo(Account::class); }
}