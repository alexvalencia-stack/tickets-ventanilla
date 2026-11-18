<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Máximo de turnos por día para cada tipo de trámite. Sin fila o null = sin límite.
        Schema::create('cupos', function (Blueprint $table) {
            $table->string('categoria')->primary();
            $table->unsignedSmallInteger('cupo')->nullable();
        });
        // 2 ventanillas x 4 horas de recepción / 15 minutos por carpeta. Se ajusta en Administración > Trámites.
        DB::table('cupos')->insert(['categoria' => 'recepcion', 'cupo' => 32]);

        Schema::table('turnos', function (Blueprint $table) {
            $table->string('cedula', 10)->nullable()->index(); // solo en turnos sacados para otro día
        });
    }

    public function down(): void
    {
        Schema::table('turnos', fn (Blueprint $table) => $table->dropColumn('cedula'));
        Schema::dropIfExists('cupos');
    }
};
