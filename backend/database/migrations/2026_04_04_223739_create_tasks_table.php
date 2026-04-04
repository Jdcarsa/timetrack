<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Qué empleado creó esta tarea
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Datos de la tarea
            $table->string('title');
            $table->text('description')->nullable();

            // Cuándo ocurre
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            // Estado actual
            $table->enum('status', ['pending', 'in_progress', 'completed'])
                  ->default('pending');

            // Recurrencia semanal
            // is_recurring = true significa que se repite cada semana
            // recur_day = 0 (lunes), 1 (martes), ..., 6 (domingo)
            $table->boolean('is_recurring')->default(false);
            $table->tinyInteger('recur_day')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};