<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\SkillMatrix;
use Illuminate\Http\Request;

class SkillMatrixController extends BaseController
{
    public function index(Request $request)
    {
        $query = SkillMatrix::query();
        $request->merge(['search_fields' => ['skill_name']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new SkillMatrix);
        $skill = SkillMatrix::create($validated);

        return $this->success($skill, 'Created', 201);
    }

    public function show(SkillMatrix $skill)
    {
        return $this->success($skill);
    }

    public function update(Request $request, SkillMatrix $skill)
    {
        $validated = $this->validatedFor($request, $skill, $skill->id);
        $skill->update($validated);

        return $this->success($skill, 'Updated');
    }

    public function destroy(SkillMatrix $skill)
    {
        $skill->delete();

        return $this->success(null, 'Deleted');
    }
}
