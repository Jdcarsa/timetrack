<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_tasks', function (Blueprint $table) {
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->primary(['schedule_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_tasks');
    }
};