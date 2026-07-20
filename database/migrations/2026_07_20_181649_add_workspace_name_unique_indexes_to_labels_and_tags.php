<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('labels', 'normalized_name')) {
            Schema::table('labels', function (Blueprint $table): void {
                $table->string('normalized_name')->default('')->after('name');
            });
        }

        if (! Schema::hasColumn('tags', 'normalized_name')) {
            Schema::table('tags', function (Blueprint $table): void {
                $table->string('normalized_name')->default('')->after('name');
            });
        }

        DB::transaction(function (): void {
            $this->mergeDuplicateMetadata('labels', 'todo_label', 'label_id', 'Unnamed label');
            $this->mergeDuplicateMetadata('tags', 'todo_tag', 'tag_id', 'unnamed-tag');

            DB::statement('CREATE UNIQUE INDEX labels_workspace_name_unique ON labels (workspace_id, normalized_name)');
            DB::statement('CREATE UNIQUE INDEX tags_workspace_name_unique ON tags (workspace_id, normalized_name)');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS labels_workspace_name_unique');
        DB::statement('DROP INDEX IF EXISTS tags_workspace_name_unique');

        if (Schema::hasColumn('labels', 'normalized_name')) {
            Schema::table('labels', function (Blueprint $table): void {
                $table->dropColumn('normalized_name');
            });
        }

        if (Schema::hasColumn('tags', 'normalized_name')) {
            Schema::table('tags', function (Blueprint $table): void {
                $table->dropColumn('normalized_name');
            });
        }
    }

    private function mergeDuplicateMetadata(
        string $table,
        string $pivotTable,
        string $pivotForeignKey,
        string $fallbackName,
    ): void {
        /** @var array<string, array{id: string, name: string, normalized_name: string}> $canonicalRecords */
        $canonicalRecords = [];
        $records = DB::table($table)
            ->select(['id', 'workspace_id', 'name'])
            ->orderBy('workspace_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($records as $record) {
            $id = (string) $record->id;
            $name = trim((string) $record->name);

            if ($name === '') {
                $name = $fallbackName.' '.substr($id, 0, 8);
            }

            $normalizedName = mb_strtolower($name, 'UTF-8');
            $groupKey = (string) $record->workspace_id."\0".$normalizedName;

            if (! isset($canonicalRecords[$groupKey])) {
                $canonicalRecords[$groupKey] = [
                    'id' => $id,
                    'name' => $name,
                    'normalized_name' => $normalizedName,
                ];

                continue;
            }

            $canonicalId = $canonicalRecords[$groupKey]['id'];
            DB::statement(
                "INSERT OR IGNORE INTO {$pivotTable} (todo_id, {$pivotForeignKey}) SELECT todo_id, ? FROM {$pivotTable} WHERE {$pivotForeignKey} = ?",
                [$canonicalId, $id],
            );
            DB::table($table)->where('id', $id)->delete();
        }

        foreach ($canonicalRecords as $canonicalRecord) {
            DB::table($table)
                ->where('id', $canonicalRecord['id'])
                ->update([
                    'name' => $canonicalRecord['name'],
                    'normalized_name' => $canonicalRecord['normalized_name'],
                ]);
        }
    }
};
