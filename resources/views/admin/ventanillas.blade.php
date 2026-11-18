@extends('layouts.app')
@section('titulo', 'Ventanillas')

@section('contenido')
    <div class="encabezado">
        <h1>Ventanillas</h1>
        <a class="btn" href="{{ route('admin.ventanillas.nuevo') }}">Nueva ventanilla</a>
    </div>
    <div class="tabla-caja">
        <table>
            <thead><tr><th>Nombre</th><th>Atiende</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @foreach ($items as $v)
                <tr>
                    <td><strong>{{ $v->nombre }}</strong></td>
                    <td>{{ $v->categoria ? \App\Models\Tramite::CATEGORIAS[$v->categoria][0] : 'Todos los trámites' }}</td>
                    <td><span class="etiqueta {{ $v->activo ? 'ok' : '' }}">{{ $v->activo ? 'Activa' : 'Inactiva' }}</span></td>
                    <td class="der">
                        <div class="fila-acciones" style="justify-content:flex-end">
                            <a class="btn secundario chico" href="{{ route('admin.ventanillas.editar', $v->id) }}">Editar</a>
                            <form method="post" action="{{ route('admin.ventanillas.eliminar', $v->id) }}" onsubmit="return confirm('¿Seguro que desea eliminar este registro?')">
                                @csrf @method('delete')
                                <button class="btn peligro chico">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
