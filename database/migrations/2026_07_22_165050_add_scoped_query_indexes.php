<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $statements = [
            'CREATE INDEX projects_workspace_position_index ON projects (workspace_id, position, id)',
            'CREATE INDEX todos_workspace_archive_pinned_position_index ON todos (workspace_id, is_archived, is_pinned DESC, position, id)',
            'CREATE INDEX todos_workspace_project_archive_position_index ON todos (workspace_id, project_id, is_archived, position, id)',
            'CREATE INDEX todos_workspace_archive_due_index ON todos (workspace_id, is_archived, due_date, id)',
            'CREATE INDEX checklists_todo_position_index ON checklists (todo_id, position, id)',
            'CREATE INDEX checklist_items_checklist_position_index ON checklist_items (checklist_id, position, id)',
            'CREATE INDEX activity_logs_workspace_created_index ON activity_logs (workspace_id, created_at DESC, id DESC)',
            'CREATE INDEX notifications_notifiable_created_index ON notifications (notifiable_type, notifiable_id, created_at DESC, id DESC)',
        ];

        foreach ($statements as $statement) {
            DB::statement($statement);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'projects_workspace_position_index',
            'todos_workspace_archive_pinned_position_index',
            'todos_workspace_project_archive_position_index',
            'todos_workspace_archive_due_index',
            'checklists_todo_position_index',
            'checklist_items_checklist_position_index',
            'activity_logs_workspace_created_index',
            'notifications_notifiable_created_index',
        ] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
