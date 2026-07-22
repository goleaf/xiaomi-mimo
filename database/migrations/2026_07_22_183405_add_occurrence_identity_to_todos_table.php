<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->uuid('recurrence_series_id')->nullable()->after('recurring_rule');
            $table->unsignedInteger('recurrence_sequence')->nullable()->after('recurrence_series_id');
            $table->date('recurrence_anchor_date')->nullable()->after('recurrence_sequence');
            $table->date('recurrence_occurrence_date')->nullable()->after('recurrence_anchor_date');
            $table->timestamp('recurrence_generated_at')->nullable()->after('recurrence_occurrence_date');
        });

        DB::table('todos')
            ->whereIn('recurring_rule', [
                'FREQ=DAILY;INTERVAL=1',
                'FREQ=WEEKLY;INTERVAL=1',
                'FREQ=MONTHLY;INTERVAL=1',
                'FREQ=YEARLY;INTERVAL=1',
            ])
            ->update([
                'recurring_rule' => DB::raw("REPLACE(recurring_rule, ';INTERVAL=1', '')"),
            ]);

        DB::table('todos')
            ->where('is_recurring', true)
            ->whereNotNull('recurring_rule')
            ->orderBy('id')
            ->eachById(function (object $todo): void {
                $anchor = $todo->due_date
                    ?? ($todo->completed_at !== null ? substr((string) $todo->completed_at, 0, 10) : null)
                    ?? ($todo->created_at !== null ? substr((string) $todo->created_at, 0, 10) : now()->toDateString());

                DB::table('todos')->where('id', $todo->id)->update([
                    'recurrence_series_id' => $todo->id,
                    'recurrence_sequence' => 0,
                    'recurrence_anchor_date' => $anchor,
                    'recurrence_occurrence_date' => $anchor,
                ]);
            }, 100, 'id');

        Schema::table('todos', function (Blueprint $table) {
            $table->unique(
                ['recurrence_series_id', 'recurrence_sequence'],
                'todos_recurrence_series_sequence_unique',
            );
            $table->unique(
                ['recurrence_series_id', 'recurrence_occurrence_date'],
                'todos_recurrence_series_date_unique',
            );
            $table->index(
                ['is_recurring', 'completed_at', 'recurrence_generated_at', 'id'],
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
            $table->dropUnique('todos_recurrence_series_date_unique');
            $table->dropUnique('todos_recurrence_series_sequence_unique');
            $table->dropColumn([
                'recurrence_series_id',
                'recurrence_sequence',
                'recurrence_anchor_date',
                'recurrence_occurrence_date',
                'recurrence_generated_at',
            ]);
        });
    }
};
