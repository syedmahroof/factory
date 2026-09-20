<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JournalLine extends Model {
    protected $fillable = ['journal_id','line_number','account_id','debit','credit','currency','exchange_rate','debit_local','credit_local','cost_center_id','profit_center_id','plant_id','department_id','production_order_id','description','reference'];
    protected $casts = ['debit' => 'decimal:4','credit' => 'decimal:4'];
    public function journal() { return $this->belongsTo(Journal::class); }
    public function account() { return $this->belongsTo(Account::class); }

}