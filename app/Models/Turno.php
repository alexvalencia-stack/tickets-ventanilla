<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

#[Fillable(['fecha', 'codigo', 'tramite_id', 'preferente', 'estado', 'ventanilla_id', 'user_id', 'llamadas', 'llamado_at', 'finalizado_at', 'cedula', 'token'])]
class Turno extends Model
{
    protected function casts(): array
    {
        return [
            'preferente' => 'boolean',
            'llamado_at' => 'datetime',
            'finalizado_at' => 'datetime',
        ];
    }

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    public function ventanilla(): BelongsTo
    {
        return $this->belongsTo(Ventanilla::class);
    }

    public function esFuturo(): bool
    {
        return $this->fecha > today()->toDateString();
    }

    public function scopeHoy(Builder $q): void
    {
        $q->where('fecha', today()->toDateString());
    }

    /** En espera, en el orden en que deben ser llamados: preferentes primero, luego por llegada. */
    public function scopeEnCola(Builder $q, ?string $categoria = null): void
    {
        $q->hoy()->where('estado', 'espera')
            ->when($categoria, fn ($q) => $q->whereRelation('tramite', 'categoria', $categoria))
            ->orderByDesc('preferente')->orderBy('id');
    }

    /** Días hábiles hacia adelante en los que se puede reservar turno. */
    public const DIAS_ADELANTO = 5;

    /**
     * Primer día con turnos libres para el trámite: hoy si aún se atiende y queda cupo,
     * o el siguiente día hábil con cupo. Null si todo está lleno.
     */
    public static function fechaPara(Tramite $tramite): ?string
    {
        $cupo = $tramite->cupo();
        $dia = today();

        // shortcut: solo salta sábados y domingos, los feriados no; agregar una tabla de feriados si hace falta.
        for ($habiles = 0; $habiles <= self::DIAS_ADELANTO; $dia = $dia->copy()->addDay()) {
            if ($dia->isWeekend()) {
                continue;
            }
            $habiles++;
            $abierto = ! $dia->isToday() || $tramite->disponible();
            // shortcut: dos kioscos a la vez pueden pasarse del cupo por uno; aceptable para una sala.
            $ocupados = $cupo === null ? 0 : self::where('fecha', $dia->toDateString())
                ->whereRelation('tramite', 'categoria', $tramite->categoria)->count();
            if ($abierto && ($cupo === null || $ocupados < $cupo)) {
                return $dia->toDateString();
            }
        }

        return null;
    }

    /** Emite el siguiente turno del día indicado (R-001, R-002…). Sin fecha = hoy. */
    public static function emitir(Tramite $tramite, bool $preferente = false, ?string $fecha = null, ?string $cedula = null): self
    {
        $fecha ??= today()->toDateString();
        $prefijo = $tramite->prefijo();

        // Dos kioscos a la vez pueden calcular el mismo número: el índice único lo impide y se reintenta.
        for ($intento = 0; ; $intento++) {
            // shortcut: el máximo se compara como texto, sirve hasta 999 turnos por tipo al día.
            $ultimo = self::where('fecha', $fecha)->where('codigo', 'like', "$prefijo-%")->max('codigo');
            $numero = $ultimo ? ((int) substr($ultimo, 2)) + 1 : 1;

            try {
                return self::create([
                    'fecha' => $fecha,
                    'codigo' => sprintf('%s-%03d', $prefijo, $numero),
                    'tramite_id' => $tramite->id,
                    'preferente' => $preferente,
                    'cedula' => $cedula,
                    'token' => Str::random(32),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                if ($intento >= 5) {
                    throw $e;
                }
            }
        }
    }

    /** Toma el siguiente turno en espera para una ventanilla. Null si no hay nadie. */
    public static function llamarSiguiente(Ventanilla $ventanilla, User $user): ?self
    {
        while ($id = self::enCola($ventanilla->categoria)->value('turnos.id')) {
            // El where estado=espera evita que dos ventanillas tomen el mismo turno.
            $tomado = self::whereKey($id)->where('estado', 'espera')->update([
                'estado' => 'llamado',
                'ventanilla_id' => $ventanilla->id,
                'user_id' => $user->id,
                'llamadas' => 1,
                'llamado_at' => now(),
            ]);
            if ($tomado) {
                return self::find($id);
            }
        }

        return null;
    }
}
