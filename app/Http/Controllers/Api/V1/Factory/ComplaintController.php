<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends BaseController
{
    public function index(Request $request)
    {
        $query = Complaint::query();
        $request->merge(['search_fields' => ['complaint_number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Complaint);
        $complaint = Complaint::create($validated);

        return $this->success($complaint, 'Created', 201);
    }

    public function show(Complaint $complaint)
    {
        return $this->success($complaint);
    }

    public function update(Request $request, Complaint $complaint)
    {
        $validated = $this->validatedFor($request, $complaint, $complaint->id);
        $complaint->update($validated);

        return $this->success($complaint, 'Updated');
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();

        return $this->success(null, 'Deleted');
    }
}
