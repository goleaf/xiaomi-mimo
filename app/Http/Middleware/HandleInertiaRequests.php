<?php

namespace App\Http\Middleware;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\WorkspaceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $user?->loadMissing('preferences');

        $navigationData = null;
        $resolveNavigation = function () use ($request, $user, &$navigationData): array {
            if (is_array($navigationData)) {
                return $navigationData;
            }

            $labels = [
                'platform' => __('navigation.platform'),
                'workspace' => __('navigation.workspace'),
                'selectWorkspace' => __('navigation.select_workspace'),
                'dashboard' => __('navigation.dashboard'),
                'tasks' => __('navigation.tasks'),
                'projects' => __('navigation.projects'),
                'calendar' => __('navigation.calendar'),
                'activity' => __('navigation.activity'),
                'notifications' => __('navigation.notifications'),
                'settings' => __('navigation.settings'),
                'manageWorkspaces' => __('navigation.manage_workspaces'),
                'recentProjects' => __('navigation.recent_projects'),
                'noProjects' => __('navigation.no_projects'),
                'switchingFailed' => __('navigation.switching_failed'),
            ];

            if (! $user) {
                return $navigationData = [
                    'workspaces' => [],
                    'currentWorkspace' => null,
                    'projects' => [],
                    'labels' => $labels,
                ];
            }

            $workspaces = $user->workspaces()
                ->withCount(['members', 'projects', 'todos'])
                ->get();
            $selectedWorkspaceId = $request->session()->get('current_workspace_id');
            $currentWorkspace = is_string($selectedWorkspaceId)
                ? $workspaces->firstWhere('id', $selectedWorkspaceId)
                : null;
            $currentWorkspace ??= $workspaces->first();
            $projects = $currentWorkspace
                ? $currentWorkspace->projects()->active()->withCount('todos')->limit(5)->get()
                : collect();

            return $navigationData = [
                'workspaces' => WorkspaceResource::collection($workspaces)->resolve($request),
                'currentWorkspace' => $currentWorkspace
                    ? (new WorkspaceResource($currentWorkspace))->resolve($request)
                    : null,
                'projects' => ProjectResource::collection($projects)->resolve($request),
                'labels' => $labels,
            ];
        };

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    ...$user->only(['id', 'name', 'email', 'email_verified_at', 'two_factor_enabled']),
                    'avatar' => is_string($user->getAttribute('avatar_path'))
                        ? route('profile.avatar.show', ['v' => $user->updated_at?->getTimestamp()])
                        : null,
                ] : null,
            ],
            'capabilities' => [
                'manageDatabaseBackups' => $user
                    ? Gate::forUser($user)->allows('manageDatabaseBackups')
                    : false,
            ],
            'currentWorkspace' => fn () => $resolveNavigation()['currentWorkspace'],
            'navigation' => fn () => $resolveNavigation(),
            'preferences' => fn () => $user?->preferences,
            'ui' => fn (): array => $this->uiTranslations(),
            'workspaceUi' => fn (): array => $this->workspaceUiTranslations(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function uiTranslations(): array
    {
        $translations = __('ui');

        return is_array($translations) ? $translations : [];
    }

    /** @return array<string, mixed> */
    private function workspaceUiTranslations(): array
    {
        $translations = __('workspace');

        return is_array($translations) ? $translations : [];
    }
}
