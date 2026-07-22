<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertExistingRelationsAreValid();

        Schema::table('passkeys', function (Blueprint $table): void {
            $table->uuid('user_id')->change();
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->uuid('notifiable_id')->change();
        });

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->uuid('tokenable_id')->change();
        });

        if (! $this->todoParentForeignKeyExists()) {
            Schema::table('todos', function (Blueprint $table): void {
                $table->foreign('parent_id')->references('id')->on('todos')->nullOnDelete();
            });
        }

        $this->createTaskDefinitionTriggers();
        $this->createParentWorkspaceTriggers();
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS todos_parent_workspace_update');
        DB::statement('DROP TRIGGER IF EXISTS todos_parent_workspace_insert');

        if ($this->todoParentForeignKeyExists()) {
            Schema::table('todos', function (Blueprint $table): void {
                $table->dropForeign(['parent_id']);
            });
        }

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->unsignedBigInteger('tokenable_id')->change();
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->unsignedBigInteger('notifiable_id')->change();
        });

        Schema::table('passkeys', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->change();
        });

        $this->createTaskDefinitionTriggers();
    }

    private function assertExistingRelationsAreValid(): void
    {
        $invalidPasskeys = DB::table('passkeys')
            ->leftJoin('users', 'users.id', '=', 'passkeys.user_id')
            ->whereNull('users.id')
            ->exists();
        $invalidNotifications = DB::table('notifications')
            ->leftJoin('users', 'users.id', '=', 'notifications.notifiable_id')
            ->where('notifications.notifiable_type', User::class)
            ->whereNull('users.id')
            ->exists();
        $invalidTokens = DB::table('personal_access_tokens')
            ->leftJoin('users', 'users.id', '=', 'personal_access_tokens.tokenable_id')
            ->where('personal_access_tokens.tokenable_type', User::class)
            ->whereNull('users.id')
            ->exists();
        $invalidParents = DB::table('todos as child')
            ->leftJoin('todos as parent', 'parent.id', '=', 'child.parent_id')
            ->whereNotNull('child.parent_id')
            ->where(function ($query): void {
                $query->whereNull('parent.id')
                    ->orWhereColumn('parent.workspace_id', '!=', 'child.workspace_id');
            })
            ->exists();

        if ($invalidPasskeys || $invalidNotifications || $invalidTokens || $invalidParents) {
            throw new RuntimeException('UUID relations or task parent links contain invalid existing data.');
        }
    }

    private function todoParentForeignKeyExists(): bool
    {
        return collect(DB::select("SELECT * FROM pragma_foreign_key_list('todos')"))
            ->contains(function (object $foreignKey): bool {
                $definition = get_object_vars($foreignKey);

                return ($definition['from'] ?? null) === 'parent_id'
                    && ($definition['table'] ?? null) === 'todos'
                    && ($definition['to'] ?? null) === 'id';
            });
    }

    private function createParentWorkspaceTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS todos_parent_workspace_insert');
        DB::statement('DROP TRIGGER IF EXISTS todos_parent_workspace_update');

        DB::statement(
            "CREATE TRIGGER todos_parent_workspace_insert
            BEFORE INSERT ON todos
            WHEN NEW.parent_id IS NOT NULL
                AND (NEW.parent_id = NEW.id
                    OR NOT EXISTS (
                    SELECT 1 FROM todos AS parent
                    WHERE parent.id = NEW.parent_id
                        AND parent.workspace_id = NEW.workspace_id
                    )
                )
            BEGIN
                SELECT RAISE(ABORT, 'Invalid task parent assignment');
            END"
        );
        DB::statement(
            "CREATE TRIGGER todos_parent_workspace_update
            BEFORE UPDATE OF parent_id, workspace_id ON todos
            WHEN NEW.parent_id IS NOT NULL
                AND (NEW.parent_id = NEW.id
                    OR NOT EXISTS (
                    SELECT 1 FROM todos AS parent
                    WHERE parent.id = NEW.parent_id
                        AND parent.workspace_id = NEW.workspace_id
                    )
                )
            BEGIN
                SELECT RAISE(ABORT, 'Invalid task parent assignment');
            END"
        );
    }

    private function createTaskDefinitionTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS todos_task_definitions_insert');
        DB::statement('DROP TRIGGER IF EXISTS todos_task_definitions_update');

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
};
