<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AccountGroup extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','code','name','type','level','parent_id','is_control_account'];
    public function company() { return $this->belongsTo(Company::class); }
    public function accounts() { return $this->hasMany(Account::class); }

}