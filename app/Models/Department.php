<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Department extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'plant_id', 'code', 'name', 'parent_id', 'is_active'];

    public function company() { return $this->belongsTo(Company::class); }
    public function plant() { return $this->belongsTo(Plant::class); }
    public function parent() { return $this->belongsTo(Department::class, 'parent_id'); }
    public function children() { return $this->hasMany(Department::class, 'parent_id'); }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }
}