<?php

// Solo las reglas que usa esta aplicación.
return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'boolean' => 'El campo :attribute no es válido.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'exists' => 'El valor elegido en :attribute no es válido.',
    'in' => 'El valor elegido en :attribute no es válido.',
    'unique' => 'Ya existe un registro con ese :attribute.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'array' => 'El campo :attribute no es válido.',
    'date_format' => 'El campo :attribute debe tener el formato hora:minutos.',
    'max' => [
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'numeric' => 'El campo :attribute no puede ser menor que :min.',
    ],
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo',
        'password' => 'contraseña',
        'tramite_id' => 'trámite',
        'cedula' => 'cédula',
        'cupo.*' => 'cupo',
        'ventanilla_id' => 'ventanilla',
        'categoria' => 'tipo',
        'hora_limite' => 'hora límite',
        'descripcion' => 'descripción',
    ],
];
