<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var list<array{key: string, name: string, translation_key: string, color: string, is_default: bool, is_completed: bool, is_completion_target: bool}> */
    private array $statuses = [
        ['key' => 'pending', 'name' => 'To do', 'translation_key' => 'tasks.statuses.pending', 'color' => '#64748b', 'is_default' => true, 'is_completed' => false, 'is_completion_target' => false],
        ['key' => 'in_progress', 'name' => 'In progress', 'translation_key' => 'tasks.statuses.in_progress', 'color' => '#f59e0b', 'is_default' => false, 'is_completed' => false, 'is_completion_target' => false],
        ['key' => 'completed', 'name' => 'Completed', 'translation_key' => 'tasks.statuses.completed', 'color' => '#22c55e', 'is_default' => false, 'is_completed' => true, 'is_completion_target' => true],
    ];

    /** @var list<array{key: string, name: string, translation_key: string, color: string, is_default: bool}> */
    private array $priorities = [
        ['key' => 'none', 'name' => 'None', 'translation_key' => 'tasks.priorities.none', 'color' => '#94a3b8', 'is_default' => true],
        ['key' => 'low', 'name' => 'Low', 'translation_key' => 'tasks.priorities.low', 'color' => '#3b82f6', 'is_default' => false],
        ['key' => 'medium', 'name' => 'Medium', 'translation_key' => 'tasks.priorities.medium', 'color' => '#eab308', 'is_default' => false],
        ['key' => 'high', 'name' => 'High', 'translation_key' => 'tasks.priorities.high', 'color' => '#f97316', 'is_default' => false],
        ['key' => 'urgent', 'name' => 'Urgent', 'translation_key' => 'tasks.priorities.urgent', 'color' => '#ef4444', 'is_default' => false],
    ];

    public function up(): void
    {
        Schema::create('task_statuses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name', 100);
            $table->string('normalized_name', 100);
            $table->string('translation_key')->nullable();
            $table->string('color', 7);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_completion_target')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->unique(['workspace_id', 'key']);
            $table->unique(['workspace_id', 'normalized_name']);
            $table->unique(['workspace_id', 'id']);
            $table->index(['workspace_id', 'position']);
            $table->index(['workspace_id', 'is_archived']);
        });

        Schema::create('task_priorities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name', 100);
            $table->string('normalized_name', 100);
            $table->string('translation_key')->nullable();
            $table->string('color', 7);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->unique(['workspace_id', 'key']);
            $table->unique(['workspace_id', 'normalized_name']);
            $table->unique(['workspace_id', 'id']);
            $table->index(['workspace_id', 'position']);
            $table->index(['workspace_id', 'is_archived']);
        });

        Schema::table('todos', function (Blueprint $table): void {
            $table->uuid('status_id')->nullable()->after('status');
            $table->uuid('priority_id')->nullable()->after('priority');
        });

        DB::transaction(function (): void {
            DB::table('workspaces')->orderBy('id')->each(function (object $workspace): void {
                $this->createWorkspaceDefinitions((string) $workspace->id);
            });

            $unmappedTasks = DB::table('todos')
                ->where(fn ($query) => $query->whereNull('status_id')->orWhereNull('priority_id'))
                ->count();
            $inconsistentTasks = DB::table('todos')
                ->join('task_statuses', 'task_statuses.id', '=', 'todos.status_id')
                ->where(fn ($query) => $query
                    ->where(fn ($nested) => $nested->where('task_statuses.is_completed', true)->whereNull('todos.completed_at'))
                    ->orWhere(fn ($nested) => $nested->where('task_statuses.is_completed', false)->whereNotNull('todos.completed_at')))
                ->count();

            if ($unmappedTasks > 0 || $inconsistentTasks > 0) {
                throw new RuntimeException('Task definitions could not be backfilled without losing task state.');
            }
        });

        DB::statement('CREATE UNIQUE INDEX task_statuses_workspace_default_unique ON task_statuses (workspace_id) WHERE is_default = 1');
        DB::statement('CREATE UNIQUE INDEX task_statuses_workspace_completion_target_unique ON task_statuses (workspace_id) WHERE is_completion_target = 1');
        DB::statement('CREATE UNIQUE INDEX task_priorities_workspace_default_unique ON task_priorities (workspace_id) WHERE is_default = 1');
        DB::statement(
            "CREATE TRIGGER task_statuses_semantics_insert
            BEFORE INSERT ON task_statuses
            WHEN (NEW.is_completion_target = 1 AND (NEW.is_completed = 0 OR NEW.is_archived = 1))
                OR (NEW.is_default = 1 AND (NEW.is_completed = 1 OR NEW.is_archived = 1))
            BEGIN
                SELECT RAISE(ABORT, 'Invalid task status semantics');
            END"
        );
        DB::statement(
            "CREATE TRIGGER task_statuses_semantics_update
            BEFORE UPDATE ON task_statuses
            WHEN (NEW.is_completion_target = 1 AND (NEW.is_completed = 0 OR NEW.is_archived = 1))
                OR (NEW.is_default = 1 AND (NEW.is_completed = 1 OR NEW.is_archived = 1))
            BEGIN
                SELECT RAISE(ABORT, 'Invalid task status semantics');
            END"
        );
        DB::statement(
            "CREATE TRIGGER task_priorities_semantics_insert
            BEFORE INSERT ON task_priorities
            WHEN NEW.is_default = 1 AND NEW.is_archived = 1
            BEGIN
                SELECT RAISE(ABORT, 'Invalid task priority semantics');
            END"
        );
        DB::statement(
            "CREATE TRIGGER task_priorities_semantics_update
            BEFORE UPDATE ON task_priorities
            WHEN NEW.is_default = 1 AND NEW.is_archived = 1
            BEGIN
                SELECT RAISE(ABORT, 'Invalid task priority semantics');
            END"
        );

        Schema::table('todos', function (Blueprint $table): void {
            $table->foreign(['workspace_id', 'status_id'])
                ->references(['workspace_id', 'id'])
                ->on('task_statuses')
                ->restrictOnDelete();
            $table->foreign(['workspace_id', 'priority_id'])
                ->references(['workspace_id', 'id'])
                ->on('task_priorities')
                ->restrictOnDelete();
            $table->index('status_id');
            $table->index('priority_id');
        });

        DB::statement(
            "CREATE TRIGGER todos_task_definitions_insert
            BEFORE INSERT ON todos
            WHEN NEW.status_id IS NULL
                OR NEW.priority_id IS NULL
                OR NOT EXISTS (
                    SELECT 1 FROM task_statuses
                    WHERE id = NEW.status_id
                        AND workspace_id = NEW.workspace_id
                        AND key = NEW.status
                )
                OR NOT EXISTS (
                    SELECT 1 FROM task_priorities
                    WHERE id = NEW.priority_id
                        AND workspace_id = NEW.workspace_id
                        AND key = NEW.priority
                )
            BEGIN
                SELECT RAISE(ABORT, 'Invalid task definition assignment');
            END"
        );
        DB::statement(
            "CREATE TRIGGER todos_task_definitions_update
            BEFORE UPDATE OF workspace_id, status, status_id, priority, priority_id ON todos
            WHEN NEW.status_id IS NULL
                OR NEW.priority_id IS NULL
                OR NOT EXISTS (
                    SELECT 1 FROM task_statuses
                    WHERE id = NEW.status_id
                        AND workspace_id = NEW.workspace_id
                        AND key = NEW.status
                )
                OR NOT EXISTS (
                    SELECT 1 FROM task_priorities
                    WHERE id = NEW.priority_id
                        AND workspace_id = NEW.workspace_id
                        AND key = NEW.priority
                )
            BEGIN
                SELECT RAISE(ABORT, 'Invalid task definition assignment');
            END"
        );
    }

    public function down(): void
    {
        $customDefinitions = DB::table('task_statuses')
            ->whereNotIn('key', array_column($this->statuses, 'key'))
            ->orWhereNull('translation_key')
            ->orWhereColumn('updated_at', '!=', 'created_at')
            ->exists()
            || DB::table('task_priorities')
                ->whereNotIn('key', array_column($this->priorities, 'key'))
                ->orWhereNull('translation_key')
                ->orWhereColumn('updated_at', '!=', 'created_at')
                ->exists()
            || DB::table('todos')->whereNotIn('status', array_column($this->statuses, 'key'))->exists()
            || DB::table('todos')->whereNotIn('priority', array_column($this->priorities, 'key'))->exists();

        if ($customDefinitions) {
            throw new RuntimeException('Task definitions contain custom data and cannot be rolled back safely.');
        }

        DB::statement('DROP TRIGGER IF EXISTS todos_task_definitions_update');
        DB::statement('DROP TRIGGER IF EXISTS todos_task_definitions_insert');

        Schema::table('todos', function (Blueprint $table): void {
            $table->dropForeign(['workspace_id', 'status_id']);
            $table->dropForeign(['workspace_id', 'priority_id']);
            $table->dropIndex(['status_id']);
            $table->dropIndex(['priority_id']);
            $table->dropColumn(['status_id', 'priority_id']);
        });

        Schema::dropIfExists('task_priorities');
        Schema::dropIfExists('task_statuses');
    }

    private function createWorkspaceDefinitions(string $workspaceId): void
    {
        $timestamp = now();
        $statusIds = [];
        $priorityIds = [];

        foreach ($this->statuses as $position => $status) {
            $id = (string) Str::uuid();
            $statusIds[$status['key']] = $id;
            DB::table('task_statuses')->insert([
                ...$status,
                'id' => $id,
                'workspace_id' => $workspaceId,
                'normalized_name' => mb_strtolower($status['name'], 'UTF-8'),
                'position' => $position,
                'is_archived' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $unknownStatuses = DB::table('todos')
            ->where('workspace_id', $workspaceId)
            ->whereNotIn('status', array_keys($statusIds))
            ->distinct()
            ->pluck('status');

        foreach ($unknownStatuses as $unknownStatus) {
            $key = (string) $unknownStatus;
            $completedCount = DB::table('todos')
                ->where('workspace_id', $workspaceId)
                ->where('status', $key)
                ->whereNotNull('completed_at')
                ->count();
            $totalCount = DB::table('todos')
                ->where('workspace_id', $workspaceId)
                ->where('status', $key)
                ->count();

            if ($key === '' || ($completedCount !== 0 && $completedCount !== $totalCount)) {
                throw new RuntimeException("Legacy task status [{$key}] has inconsistent completion semantics.");
            }

            $id = (string) Str::uuid();
            $name = Str::limit('Custom: '.Str::headline($key), 100, '');
            $statusIds[$key] = $id;
            DB::table('task_statuses')->insert([
                'id' => $id,
                'workspace_id' => $workspaceId,
                'key' => $key,
                'name' => $name,
                'normalized_name' => mb_strtolower($name, 'UTF-8'),
                'translation_key' => null,
                'color' => '#64748b',
                'position' => count($statusIds) - 1,
                'is_default' => false,
                'is_completed' => $completedCount === $totalCount,
                'is_completion_target' => false,
                'is_archived' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        foreach ($this->priorities as $position => $priority) {
            $id = (string) Str::uuid();
            $priorityIds[$priority['key']] = $id;
            DB::table('task_priorities')->insert([
                ...$priority,
                'id' => $id,
                'workspace_id' => $workspaceId,
                'normalized_name' => mb_strtolower($priority['name'], 'UTF-8'),
                'position' => $position,
                'is_archived' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $unknownPriorities = DB::table('todos')
            ->where('workspace_id', $workspaceId)
            ->whereNotIn('priority', array_keys($priorityIds))
            ->distinct()
            ->pluck('priority');

        foreach ($unknownPriorities as $unknownPriority) {
            $key = (string) $unknownPriority;

            if ($key === '') {
                throw new RuntimeException('Legacy task priority keys cannot be empty.');
            }

            $id = (string) Str::uuid();
            $name = Str::limit('Custom: '.Str::headline($key), 100, '');
            $priorityIds[$key] = $id;
            DB::table('task_priorities')->insert([
                'id' => $id,
                'workspace_id' => $workspaceId,
                'key' => $key,
                'name' => $name,
                'normalized_name' => mb_strtolower($name, 'UTF-8'),
                'translation_key' => null,
                'color' => '#94a3b8',
                'position' => count($priorityIds) - 1,
                'is_default' => false,
                'is_archived' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        foreach ($statusIds as $key => $id) {
            DB::table('todos')
                ->where('workspace_id', $workspaceId)
                ->where('status', $key)
                ->update(['status_id' => $id]);
        }

        foreach ($priorityIds as $key => $id) {
            DB::table('todos')
                ->where('workspace_id', $workspaceId)
                ->where('priority', $key)
                ->update(['priority_id' => $id]);
        }
    }
};
