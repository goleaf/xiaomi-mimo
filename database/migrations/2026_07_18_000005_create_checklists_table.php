<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('todo_id');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('todo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklists');
    }
};
