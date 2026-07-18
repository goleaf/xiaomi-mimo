<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_label', function (Blueprint $table) {
            $table->uuid('todo_id')->constrained()->cascadeOnDelete();
            $table->uuid('label_id')->constrained()->cascadeOnDelete();
            $table->primary(['todo_id', 'label_id']);

            $table->index('label_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_label');
    }
};
