<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends BaseController
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');
        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }
        if ($module = $request->get('module')) {
            $query->where('auditable_type', 'LIKE', "%{$module}%");
        }

        return $this->success($this->paginated($query, $request, ['user']));
    }
}
