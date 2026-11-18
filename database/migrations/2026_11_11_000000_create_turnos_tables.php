<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramites', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('categoria'); // recepcion | entrega | informacion
            $table->text('requisitos')->nullable(); // uno por línea
            $table->time('hora_limite')->nullable(); // después de esta hora no se emiten turnos
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('ventanillas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria')->nullable(); // null = atiende todo
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('operador'); // admin | operador
            $table->foreignId('ventanilla_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('codigo', 10);
            $table->foreignId('tramite_id')->constrained();
            $table->boolean('preferente')->default(false);
            $table->string('estado')->default('espera'); // espera | llamado | atendido | ausente
            $table->foreignId('ventanilla_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('llamadas')->default(0);
            $table->timestamp('llamado_at')->nullable();
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamps();

            $table->unique(['fecha', 'codigo']);
            $table->index(['fecha', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ventanilla_id');
            $table->dropColumn('rol');
        });
        Schema::dropIfExists('ventanillas');
        Schema::dropIfExists('tramites');
    }
};
