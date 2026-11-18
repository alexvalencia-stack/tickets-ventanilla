<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Fillable(['nombre', 'descripcion', 'categoria', 'requisitos', 'hora_limite', 'activo', 'orden'])]
class Tramite extends Model
{
    /** categoria => [título, prefijo del turno] */
    public const CATEGORIAS = [
        'recepcion' => ['Recepción de documentos', 'R'],
        'entrega' => ['Entrega de documentos', 'E'],
        'informacion' => ['Información y consultas', 'I'],
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function prefijo(): string
    {
        return self::CATEGORIAS[$this->categoria][1];
    }

    /** @return string[] */
    public function listaRequisitos(): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", (string) $this->requisitos))));
    }

    /** Máximo de turnos por día de su tipo. Null = sin límite (y sin pedir cédula). */
    public function cupo(): ?int
    {
        return DB::table('cupos')->where('categoria', $this->categoria)->value('cupo');
    }

    public function horaLimiteCorta(): ?string
    {
        return $this->hora_limite ? substr($this->hora_limite, 0, 5) : null;
    }

    /** ¿Se puede sacar turno ahora mismo? */
    public function disponible(): bool
    {
        return $this->activo && (! $this->hora_limite || now()->format('H:i:s') < $this->hora_limite);
    }
}
