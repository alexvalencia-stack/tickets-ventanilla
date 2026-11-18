<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre', 'categoria', 'activo'])]
class Ventanilla extends Model
{
    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
