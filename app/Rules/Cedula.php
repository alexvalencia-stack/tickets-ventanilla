<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Cédula ecuatoriana de persona natural: provincia válida, tercer dígito < 6 y dígito verificador (módulo 10). */
class Cedula implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::valida((string) $value)) {
            $fail('El número de cédula no es válido. Revise que esté bien escrito.');
        }
    }

    public static function valida(string $cedula): bool
    {
        if (! preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }
        $provincia = (int) substr($cedula, 0, 2);
        if (! (($provincia >= 1 && $provincia <= 24) || $provincia === 30) || $cedula[2] >= 6) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $producto = $cedula[$i] * ($i % 2 === 0 ? 2 : 1);
            $suma += $producto > 9 ? $producto - 9 : $producto;
        }

        return (10 - $suma % 10) % 10 === (int) $cedula[9];
    }
}
