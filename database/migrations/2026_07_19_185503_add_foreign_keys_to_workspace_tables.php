<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('workspace_members', function (Blueprint $table): void {
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
        });

        Schema::table('todos', function (Blueprint $table): void {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('checklists', function (Blueprint $table): void {
            $table->foreign('todo_id')->references('id')->on('todos')->cascadeOnDelete();
        });

        Schema::table('checklist_items', function (Blueprint $table): void {
            $table->foreign('checklist_id')->references('id')->on('checklists')->cascadeOnDelete();
        });

        Schema::table('labels', function (Blueprint $table): void {
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
        });

        Schema::table('tags', function (Blueprint $table): void {
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
        });

        Schema::table('todo_label', function (Blueprint $table): void {
            $table->foreign('todo_id')->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('label_id')->references('id')->on('labels')->cascadeOnDelete();
        });

        Schema::table('todo_tag', function (Blueprint $table): void {
            $table->foreign('todo_id')->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->foreign('todo_id')->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('reminders', function (Blueprint $table): void {
            $table->foreign('todo_id')->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('attachments', function (Blueprint $table): void {
            $table->foreign('todo_id')->references('id')->on('todos')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
        });

        Schema::table('user_preferences', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['workspace_id']);
        });

        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropForeign(['todo_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('reminders', function (Blueprint $table): void {
            $table->dropForeign(['todo_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropForeign(['todo_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('todo_tag', function (Blueprint $table): void {
            $table->dropForeign(['todo_id']);
            $table->dropForeign(['tag_id']);
        });

        Schema::table('todo_label', function (Blueprint $table): void {
            $table->dropForeign(['todo_id']);
            $table->dropForeign(['label_id']);
        });

        Schema::table('tags', function (Blueprint $table): void {
            $table->dropForeign(['workspace_id']);
        });

        Schema::table('labels', function (Blueprint $table): void {
            $table->dropForeign(['workspace_id']);
        });

        Schema::table('checklist_items', function (Blueprint $table): void {
            $table->dropForeign(['checklist_id']);
        });

        Schema::table('checklists', function (Blueprint $table): void {
            $table->dropForeign(['todo_id']);
        });

        Schema::table('todos', function (Blueprint $table): void {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['workspace_id']);
            $table->dropForeign(['assigned_to']);
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropForeign(['workspace_id']);
        });

        Schema::table('workspace_members', function (Blueprint $table): void {
            $table->dropForeign(['workspace_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropForeign(['owner_id']);
        });
    }
};
