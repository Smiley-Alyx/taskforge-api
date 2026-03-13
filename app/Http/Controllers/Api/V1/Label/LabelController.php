<?php

namespace App\Http\Controllers\Api\V1\Label;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Label\StoreLabelRequest;
use App\Http\Requests\Api\V1\Label\UpdateLabelRequest;
use App\Http\Resources\Api\V1\LabelResource;
use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $labels = Label::query()
            ->where('workspace_id', $workspace->getKey())
            ->orderBy('name')
            ->paginate(50);

        return LabelResource::collection($labels);
    }

    public function store(StoreLabelRequest $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $label = Label::query()->create([
            'workspace_id' => $workspace->getKey(),
            'name' => $request->string('name')->toString(),
            'color' => $request->string('color')->toString(),
        ]);

        return (new LabelResource($label))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateLabelRequest $request, Workspace $workspace, Label $label)
    {
        if ((int) $label->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('update', $label);

        $label->update($request->validated());

        return new LabelResource($label);
    }

    public function destroy(Request $request, Workspace $workspace, Label $label)
    {
        if ((int) $label->workspace_id !== (int) $workspace->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $label);

        $label->delete();

        return response()->json(null, 204);
    }
}
