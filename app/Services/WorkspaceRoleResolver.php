<?php

namespace App\Services;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

class WorkspaceRoleResolver
{
    public function resolve(User $user, Workspace $workspace): ?WorkspaceRole
    {
        if ($workspace->owner_id === $user->getKey()) {
            return WorkspaceRole::Owner;
        }

        $role = $workspace->members()
            ->where('user_id', $user->getKey())
            ->value('role');

        return $role ? WorkspaceRole::tryFrom($role) : null;
    }
}
