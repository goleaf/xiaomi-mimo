<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\Workspace;
use App\Policies\AttachmentPolicy;
use App\Policies\CommentPolicy;
use App\Policies\DatabaseBackupPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReminderPolicy;
use App\Policies\TodoPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Reminder::class => ReminderPolicy::class,
        Workspace::class => WorkspacePolicy::class,
        Project::class => ProjectPolicy::class,
        Todo::class => TodoPolicy::class,
        Comment::class => CommentPolicy::class,
        Attachment::class => AttachmentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manageDatabaseBackups', [DatabaseBackupPolicy::class, 'manage']);
    }
}
