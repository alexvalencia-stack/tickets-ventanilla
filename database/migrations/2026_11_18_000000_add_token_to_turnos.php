<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    // El enlace público del ticket usa este código y no el id: con la cédula impresa,
    // un id correlativo permitiría ver los turnos (y cédulas) de otras personas.
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->string('token', 32)->nullable()->unique();
        });
        foreach (DB::table('turnos')->pluck('id') as $id) {
            DB::table('turnos')->where('id', $id)->update(['token' => Str::random(32)]);
        }
    }

    public function down(): void
    {
        Schema::table('turnos', fn (Blueprint $table) => $table->dropColumn('token'));
    }
};
