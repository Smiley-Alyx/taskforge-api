<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Task;
use App\Models\User;
use App\Services\WorkspaceRoleResolver;

class TaskPolicy
{
    public function __construct(
        private readonly WorkspaceRoleResolver $roles,
    ) {
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        $role = $this->roles->resolve($user, $task->workspace);

        return $role !== null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        $role = $this->roles->resolve($user, $task->workspace);

        return $role?->atLeast(WorkspaceRole::Member) ?? false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        $role = $this->roles->resolve($user, $task->workspace);

        return $role?->atLeast(WorkspaceRole::Member) ?? false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
