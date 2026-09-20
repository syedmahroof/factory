<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Plant extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'company_id', 'code', 'name', 'address', 'city', 'state', 'country',
        'phone', 'email', 'timezone', 'is_active',
    ];

    /**
     * The table keeps a separate auto-increment bigint `id` and a `uuid` column,
     * so the UUID belongs in `uuid`, not in the primary key.
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function warehouses() { return $this->hasMany(Warehouse::class); }
    public function workCenters() { return $this->hasMany(WorkCenter::class); }
}