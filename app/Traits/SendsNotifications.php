<?php

namespace App\Traits;

use App\Notifications\FactoryNotification;
use App\Models\User;

trait SendsNotifications
{
    protected function notifyUser(User $user, string $title, string $message, string $module = 'system', string $type = 'info', ?string $url = null): void
    {
        $user->notify(new FactoryNotification($title, $message, $module, $type, $url));
    }

    protected function notifyRole(string $roleName, string $title, string $message, string $module = 'system', string $type = 'info', ?string $url = null): void
    {
        $role = \App\Models\Role::where('name', $roleName)->first();
        if ($role) {
            foreach ($role->users as $user) {
                $this->notifyUser($user, $title, $message, $module, $type, $url);
            }
        }
    }

    protected function logAudit(string $action, ?object $model = null, ?array $old = null, ?array $new = null): void
    {
        if (class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'auditable_type' => $model ? get_class($model) : null,
                'auditable_id' => $model?->id,
                'action' => $action,
                'old_values' => $old,
                'new_values' => $new,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
