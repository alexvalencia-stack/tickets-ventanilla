@extends('layouts.app')
@section('titulo', 'Usuarios')

@section('contenido')
    <div class="encabezado">
        <h1>Usuarios</h1>
        <a class="btn" href="{{ route('admin.usuarios.nuevo') }}">Nuevo usuario</a>
    </div>
    <div class="tabla-caja">
        <table>
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Ventanilla</th><th></th></tr></thead>
            <tbody>
            @foreach ($items as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td>{{ $u->email }}</td>
                    <td><span class="etiqueta {{ $u->esAdmin() ? 'aviso' : '' }}">{{ $u->esAdmin() ? 'Administrador' : 'Operador' }}</span></td>
                    <td>{{ $u->ventanilla?->nombre ?? '—' }}</td>
                    <td class="der">
                        <div class="fila-acciones" style="justify-content:flex-end">
                            <a class="btn secundario chico" href="{{ route('admin.usuarios.editar', $u->id) }}">Editar</a>
                            @unless ($u->is(auth()->user()))
                                <form method="post" action="{{ route('admin.usuarios.eliminar', $u->id) }}" onsubmit="return confirm('¿Seguro que desea eliminar este registro?')">
                                    @csrf @method('delete')
                                    <button class="btn peligro chico">Eliminar</button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
