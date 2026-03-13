<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\User;
use App\Services\WorkspaceRoleResolver;

class InvitationPolicy
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
    public function view(User $user, Invitation $invitation): bool
    {
        $role = $this->roles->resolve($user, $invitation->workspace);

        return $role?->atLeast(WorkspaceRole::Admin) ?? false;
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
    public function update(User $user, Invitation $invitation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        $role = $this->roles->resolve($user, $invitation->workspace);

        return $role?->atLeast(WorkspaceRole::Admin) ?? false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invitation $invitation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invitation $invitation): bool
    {
        return false;
    }
}
