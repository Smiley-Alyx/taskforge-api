<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Comment;
use App\Models\User;
use App\Services\WorkspaceRoleResolver;

class CommentPolicy
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
    public function view(User $user, Comment $comment): bool
    {
        $role = $this->roles->resolve($user, $comment->workspace);

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
    public function update(User $user, Comment $comment): bool
    {
        if ((int) $comment->author_id === (int) $user->getKey()) {
            $role = $this->roles->resolve($user, $comment->workspace);

            return $role?->atLeast(WorkspaceRole::Member) ?? false;
        }

        $role = $this->roles->resolve($user, $comment->workspace);

        return $role?->atLeast(WorkspaceRole::Admin) ?? false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $this->update($user, $comment);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        return false;
    }
}
