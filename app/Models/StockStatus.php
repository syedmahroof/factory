<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockStatus extends Model {
    protected $fillable = ['code','name','color','available_for_production','available_for_sales','available_for_issue'];

}