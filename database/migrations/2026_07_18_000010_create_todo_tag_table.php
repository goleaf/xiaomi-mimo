<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_tag', function (Blueprint $table) {
            $table->uuid('todo_id');
            $table->uuid('tag_id');
            $table->primary(['todo_id', 'tag_id']);

            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_tag');
    }
};
