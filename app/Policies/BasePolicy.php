<?php
namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BasePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_any_' . $this->getModuleName());
    }

    public function view(User $user, $model): bool
    {
        return $user->hasPermission('view_' . $this->getModuleName());
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_' . $this->getModuleName());
    }

    public function update(User $user, $model): bool
    {
        return $user->hasPermission('update_' . $this->getModuleName());
    }

    public function delete(User $user, $model): bool
    {
        return $user->hasPermission('delete_' . $this->getModuleName());
    }

    protected function getModuleName(): string
    {
        $class = class_basename(static::class);
        return strtolower(preg_replace('/Policy$/', '', $class));
    }

    /**
     * Scope check: user can only access data within their assigned company/plant/warehouse
     */
    protected function withinScope(User $user, $model): bool
    {
        // Super admin bypasses scope
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check company scope
        if (method_exists($model, 'company_id') && isset($model->company_id)) {
            $allowedCompanyIds = $user->companies()->pluck('companies.id');
            if (!$allowedCompanyIds->contains($model->company_id)) {
                return false;
            }
        }

        // Check plant scope
        if (method_exists($model, 'plant_id') && isset($model->plant_id)) {
            $allowedPlantIds = $user->plants()->pluck('plants.id');
            if (!$allowedPlantIds->contains($model->plant_id)) {
                return false;
            }
        }

        return true;
    }
}
