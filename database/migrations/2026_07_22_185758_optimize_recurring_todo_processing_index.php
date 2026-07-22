<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex('todos_recurrence_processing_index');
            $table->index(
                ['is_recurring', 'recurrence_generated_at', 'completed_at', 'id'],
                'todos_recurrence_processing_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex('todos_recurrence_processing_index');
            $table->index(
                ['is_recurring', 'completed_at', 'recurrence_generated_at', 'id'],
                'todos_recurrence_processing_index',
            );
        });
    }
};
