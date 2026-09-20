<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasUuids;

    /**
     * The table keeps a separate auto-increment bigint `id` and a `uuid` column,
     * so the UUID belongs in `uuid`, not in the primary key.
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar',
        'company_id', 'plant_id', 'department_id',
        'is_active', 'last_login_at', 'locale', 'timezone',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company() { return $this->belongsTo(Company::class); }
    public function plant() { return $this->belongsTo(Plant::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function roles() { return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id'); }
    public function permissions() { return $this->belongsToMany(Permission::class, 'model_has_permissions', 'model_id', 'permission_id'); }

    // RBAC helpers
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super_admin')) return true;
        return $this->permissions->contains('name', $permission);
    }

    public function assignRole(string $role): self
    {
        $roleModel = Role::where('name', $role)->first();
        if ($roleModel && !$this->roles->contains($roleModel->id)) {
            $this->roles()->attach($roleModel);
        }
        return $this;
    }

    public function givePermissionTo(string $permission): self
    {
        $permModel = Permission::where('name', $permission)->first();
        if ($permModel && !$this->permissions->contains($permModel->id)) {
            $this->permissions()->attach($permModel);
        }
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('super_admin') || $this->hasRole('company_admin');
    }
}
