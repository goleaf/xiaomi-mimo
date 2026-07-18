<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->uuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->string('priority')->default('none');
            $table->date('due_date')->nullable();
            $table->date('start_date')->nullable();
            $table->unsignedInteger('estimated_time')->nullable();
            $table->unsignedInteger('spent_time')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_rule')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('workspace_id');
            $table->index('project_id');
            $table->index('assigned_to');
            $table->index('parent_id');
            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
            $table->index('is_archived');
            $table->index('is_pinned');
            $table->index('is_favorite');
            $table->index('completed_at');
            $table->index(['project_id', 'status']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
