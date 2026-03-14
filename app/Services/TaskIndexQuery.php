<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TaskIndexQuery
{
    public function build(Request $request, Project $project): Builder
    {
        $query = Task::query()->where('project_id', $project->getKey());

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', (int) $request->input('assignee_id'));
        }

        if ($request->filled('due_date') && ! $request->filled('due_from') && ! $request->filled('due_to')) {
            $query->whereDate('due_at', '=', $request->date('due_date')->toDateString());
        }

        if ($request->filled('due_from')) {
            $query->where('due_at', '>=', $request->date('due_from')->toDateTimeString());
        }

        if ($request->filled('due_to')) {
            $query->where('due_at', '<=', $request->date('due_to')->toDateTimeString());
        }

        $sort = $request->string('sort')->toString();
        $allowedSorts = [
            'id',
            'created_at',
            'updated_at',
            'due_at',
            'priority',
            'status',
            'number',
        ];

        if ($sort !== '') {
            foreach (array_filter(explode(',', $sort)) as $field) {
                $direction = 'asc';
                $name = $field;

                if (str_starts_with($field, '-')) {
                    $direction = 'desc';
                    $name = substr($field, 1);
                }

                if (in_array($name, $allowedSorts, true)) {
                    $query->orderBy($name, $direction);
                }
            }
        } else {
            $query->orderByDesc('id');
        }

        return $query;
    }

    public function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 20);

        return max(1, min(100, $perPage));
    }
}
