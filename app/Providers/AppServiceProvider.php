<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Invitation;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Policies\ActivityLogPolicy;
use App\Policies\CommentPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\LabelPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\WorkspacePolicy;
use App\Services\WorkspaceRoleResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WorkspaceRoleResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Label::class, LabelPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
    }
}
