<?php

namespace Tests\Feature;

use App\Models\Tramite;
use App\Models\Turno;
use App\Models\User;
use App\Models\Ventanilla;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TurnosTest extends TestCase
{
    use RefreshDatabase;

    private Tramite $recepcion;

    private Tramite $entrega;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-16 09:00')); // un lunes
        DB::table('cupos')->updateOrInsert(['categoria' => 'recepcion'], ['cupo' => 32]);
        $this->recepcion = Tramite::create(['nombre' => 'Certificado', 'categoria' => 'recepcion', 'hora_limite' => '12:00', 'requisitos' => "Cédula\nEscritura"])->fresh(); // fresh: trae los valores por defecto (activo)
        $this->entrega = Tramite::create(['nombre' => 'Retiro', 'categoria' => 'entrega'])->fresh();
    }

    private function operador(?string $categoria = null): User
    {
        $ventanilla = Ventanilla::create(['nombre' => 'Ventanilla '.(Ventanilla::count() + 1), 'categoria' => $categoria]);

        return User::factory()->create(['ventanilla_id' => $ventanilla->id]);
    }

    public function test_los_turnos_se_numeran_por_tipo_y_por_dia(): void
    {
        $this->assertSame('R-001', Turno::emitir($this->recepcion)->codigo);
        $this->assertSame('R-002', Turno::emitir($this->recepcion)->codigo);
        $this->assertSame('E-001', Turno::emitir($this->entrega)->codigo);

        $this->travelTo(today()->addDay()->setTime(9, 0));
        $this->assertSame('R-001', Turno::emitir($this->recepcion)->codigo);
    }

    public function test_el_kiosco_muestra_requisitos_y_emite_el_turno(): void
    {
        $this->get('/')->assertOk()->assertSee('Certificado')->assertSee('Turnos para hoy hasta las 12:00');
        $this->get("/tramites/{$this->recepcion->id}")->assertOk()->assertSee('Escritura')->assertSee('Sacar turno')->assertSee('Número de cédula');

        // Con cupo, también los turnos para hoy piden cédula.
        $this->from('/')->post('/turnos', ['tramite_id' => $this->recepcion->id])->assertSessionHasErrors('cedula');
        $this->assertSame(0, Turno::count());

        $this->post('/turnos', ['tramite_id' => $this->recepcion->id, 'preferente' => '1', 'cedula' => '1712345675'])
            ->assertRedirect('/turnos/'.Turno::first()->token);
        $this->assertSame([today()->toDateString(), '1712345675'], [Turno::first()->fecha, Turno::first()->cedula]);
        $this->assertTrue(Turno::first()->preferente);
        $this->get('/turnos/'.Turno::first()->token)->assertOk()->assertSee('R-001')->assertSee('Usted es el siguiente')
            ->assertSee('Cédula: <strong>1712345675</strong>', false)->assertSee('Guardar como imagen');
        // El id correlativo no abre el ticket: así nadie puede recorrer las cédulas de los demás.
        $this->get('/turnos/'.Turno::first()->id)->assertNotFound();
    }

    public function test_despues_de_la_hora_limite_el_turno_pasa_al_siguiente_dia_habil_y_pide_cedula(): void
    {
        $this->travelTo(today()->setTime(12, 0)); // lunes 12:00
        $martes = today()->addDay()->toDateString();

        $this->get('/')->assertOk()->assertSee('Hoy ya no hay turnos');
        $this->get("/tramites/{$this->recepcion->id}")->assertOk()->assertSee('Número de cédula')->assertSee('Sacar turno para el martes 17');

        // Sin cédula, mal escrita o inventada (dígito verificador errado), no se reserva.
        foreach ([[], ['cedula' => '123'], ['cedula' => '0912345678'], ['cedula' => '9912345675']] as $extra) {
            $this->from('/')->post('/turnos', ['tramite_id' => $this->recepcion->id] + $extra)->assertSessionHasErrors('cedula');
        }
        $this->assertSame(0, Turno::count());

        $this->post('/turnos', ['tramite_id' => $this->recepcion->id, 'cedula' => '0912345675'])->assertRedirect();
        $turno = Turno::first();
        $this->assertSame([$martes, 'R-001', '0912345675'], [$turno->fecha, $turno->codigo, $turno->cedula]);
        $this->get("/turnos/{$turno->token}")->assertOk()->assertSee('Válido solo el')->assertSee('martes 17 de noviembre');

        // Una cédula, un turno pendiente.
        $this->from('/')->post('/turnos', ['tramite_id' => $this->recepcion->id, 'cedula' => '0912345675'])->assertSessionHasErrors('cedula');
        $this->assertSame(1, Turno::count());

        // Las entregas no tienen hora límite ni cupo: siguen saliendo para hoy y sin cédula.
        $this->post('/turnos', ['tramite_id' => $this->entrega->id])->assertRedirect();
        $this->assertSame([today()->toDateString(), null], [Turno::where('codigo', 'E-001')->value('fecha'), Turno::where('codigo', 'E-001')->value('cedula')]);
        $this->get("/tramites/{$this->entrega->id}")->assertOk()->assertDontSee('Número de cédula');

        // Al día siguiente el reservado es el primero de la fila y quien llega esa mañana sigue la numeración.
        $this->travelTo(today()->addDay()->setTime(8, 30));
        $this->assertSame('R-002', Turno::emitir($this->recepcion, fecha: Turno::fechaPara($this->recepcion))->codigo);
        $operador = $this->operador('recepcion');
        $this->assertSame('R-001', Turno::llamarSiguiente($operador->ventanilla, $operador)->codigo);
    }

    public function test_con_el_cupo_lleno_se_pasa_al_dia_siguiente_y_asi_sucesivamente(): void
    {
        DB::table('cupos')->where('categoria', 'recepcion')->update(['cupo' => 2]);
        $dia = fn (int $n) => today()->addDays($n)->toDateString();

        // Lunes 9:00: dos para hoy, dos para el martes, el quinto para el miércoles.
        $fechas = [];
        for ($i = 0; $i < 5; $i++) {
            $fechas[] = Turno::emitir($this->recepcion, fecha: Turno::fechaPara($this->recepcion))->fecha;
        }
        $this->assertSame([$dia(0), $dia(0), $dia(1), $dia(1), $dia(2)], $fechas);

        // El viernes por la tarde se salta el fin de semana.
        $this->travelTo(today()->addDays(4)->setTime(15, 0));
        $this->assertSame(today()->addDays(3)->toDateString(), Turno::fechaPara($this->recepcion));

        // Con todos los días llenos no se entrega turno.
        DB::table('cupos')->where('categoria', 'recepcion')->update(['cupo' => 1]);
        for ($d = today()->addDays(3), $n = 0; $n < Turno::DIAS_ADELANTO; $d = $d->copy()->addDay(), $n++) {
            Turno::emitir($this->recepcion, fecha: $d->toDateString());
        }
        $this->assertNull(Turno::fechaPara($this->recepcion));
        $this->from('/')->post('/turnos', ['tramite_id' => $this->recepcion->id, 'cedula' => '0912345675'])->assertSessionHasErrors('tramite_id');
        $this->get("/tramites/{$this->recepcion->id}")->assertOk()->assertSee('Escritura')->assertDontSee('Sacar turno');
    }

    public function test_el_administrador_cambia_los_cupos(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin']));
        $this->post('/admin/cupos', ['cupo' => ['recepcion' => '40', 'entrega' => '']])->assertRedirect();
        $this->assertSame([40, null], [DB::table('cupos')->where('categoria', 'recepcion')->value('cupo'), DB::table('cupos')->where('categoria', 'entrega')->value('cupo')]);
        $this->get('/admin/tramites')->assertOk()->assertSee('Turnos por día');
    }

    public function test_el_ciudadano_recupera_su_turno_con_la_cedula(): void
    {
        $this->get('/')->assertSee('Recupérelo con su número de cédula');
        $this->get('/recuperar')->assertOk()->assertSee('Buscar mi turno');

        $turno = Turno::emitir($this->recepcion, fecha: today()->addDay()->toDateString(), cedula: '1712345675');
        $this->post('/recuperar', ['cedula' => '1712345675'])->assertRedirect("/turnos/{$turno->token}");

        // Cédula inválida o sin turno pendiente: mensaje claro, no se revela nada.
        $this->from('/recuperar')->post('/recuperar', ['cedula' => '1712345678'])->assertSessionHasErrors('cedula');
        $this->from('/recuperar')->post('/recuperar', ['cedula' => '0912345675'])->assertSessionHasErrors('cedula');

        // Un turno ya atendido, o de una fecha pasada, no se recupera.
        $turno->update(['estado' => 'atendido']);
        $this->from('/recuperar')->post('/recuperar', ['cedula' => '1712345675'])->assertSessionHasErrors('cedula');
        $turno->update(['estado' => 'espera', 'fecha' => today()->subDay()->toDateString()]);
        $this->from('/recuperar')->post('/recuperar', ['cedula' => '1712345675'])->assertSessionHasErrors('cedula');
    }

    public function test_recuperar_turno_tiene_limite_de_intentos(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/recuperar', ['cedula' => '0912345675']);
        }
        $this->post('/recuperar', ['cedula' => '0912345675'])->assertStatus(429);
    }

    public function test_se_llama_primero_al_preferente_y_luego_por_orden_de_llegada(): void
    {
        Turno::emitir($this->recepcion);
        Turno::emitir($this->entrega);
        Turno::emitir($this->recepcion, preferente: true);
        $operador = $this->operador();

        $orden = [];
        while ($turno = Turno::llamarSiguiente($operador->ventanilla, $operador)) {
            $orden[] = $turno->codigo;
        }
        $this->assertSame(['R-002', 'R-001', 'E-001'], $orden);
    }

    public function test_una_ventanilla_de_entregas_solo_llama_entregas(): void
    {
        Turno::emitir($this->recepcion);
        Turno::emitir($this->entrega);
        $operador = $this->operador('entrega');

        $this->assertSame('E-001', Turno::llamarSiguiente($operador->ventanilla, $operador)->codigo);
        $this->assertNull(Turno::llamarSiguiente($operador->ventanilla, $operador));
    }

    public function test_flujo_del_operador_llamar_rellamar_y_finalizar(): void
    {
        Turno::emitir($this->recepcion);
        Turno::emitir($this->recepcion);
        $operador = $this->operador();
        $this->actingAs($operador);

        $this->get('/ventanilla')->assertOk()->assertSee('Llamar siguiente');
        $this->post('/ventanilla/llamar')->assertRedirect();
        $turno = Turno::where('codigo', 'R-001')->first();
        $this->assertSame(['llamado', $operador->ventanilla_id], [$turno->estado, $turno->ventanilla_id]);

        // Con un turno abierto no puede tomar otro.
        $this->post('/ventanilla/llamar')->assertSessionHasErrors('turno');
        $this->assertSame(1, Turno::where('estado', 'llamado')->count());
        $this->get('/ventanilla')->assertOk()->assertSee('Finalizar atención')->assertSee('Escritura');

        $this->post("/ventanilla/turnos/{$turno->id}/rellamar")->assertRedirect();
        $this->assertSame(2, $turno->fresh()->llamadas);
        $this->getJson('/pantalla/datos')->assertOk()
            ->assertJsonPath('turnos.0.codigo', 'R-001')->assertJsonPath('turnos.0.clave', "{$turno->id}:2")
            ->assertJsonPath('turnos.0.activo', true)->assertJsonPath('en_espera', 1);

        $this->post("/ventanilla/turnos/{$turno->id}/atendido")->assertRedirect();
        $this->assertSame('atendido', $turno->fresh()->estado);
        $this->assertNotNull($turno->fresh()->finalizado_at);

        // Un turno ya cerrado, o de otro operador, no se puede tocar.
        $this->post("/ventanilla/turnos/{$turno->id}/ausente")->assertForbidden();
        $this->post('/ventanilla/llamar');
        $this->actingAs($this->operador())->post('/ventanilla/turnos/'.Turno::where('codigo', 'R-002')->value('id').'/ausente')->assertForbidden();
    }

    public function test_solo_el_administrador_entra_al_panel(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/ventanilla')->assertRedirect('/login');
        $this->actingAs($this->operador())->get('/admin/tramites')->assertForbidden();

        $admin = User::factory()->create(['rol' => 'admin']);
        Turno::emitir($this->recepcion);
        foreach (['/admin', '/admin/tramites', '/admin/tramites/nuevo', "/admin/tramites/{$this->recepcion->id}",
            '/admin/ventanillas', '/admin/ventanillas/nuevo', '/admin/usuarios', '/admin/usuarios/nuevo', "/admin/usuarios/{$admin->id}"] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->get('/pantalla')->assertOk();
    }

    public function test_el_administrador_edita_tramites_y_usuarios(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($admin);

        $this->post("/admin/tramites/{$this->recepcion->id}", [
            'nombre' => 'Certificado de avalúo', 'categoria' => 'recepcion', 'hora_limite' => '13:30', 'orden' => 1, 'requisitos' => 'Cédula', 'activo' => '1',
        ])->assertRedirect('/admin/tramites');
        $this->assertSame(['Certificado de avalúo', '13:30'], [$this->recepcion->fresh()->nombre, $this->recepcion->fresh()->horaLimiteCorta()]);

        $this->post('/admin/usuarios', ['name' => 'Ana', 'email' => 'ana@catastro.test', 'rol' => 'operador', 'password' => 'clave-segura'])->assertRedirect('/admin/usuarios');
        $this->post('/logout');
        $this->post('/login', ['email' => 'ana@catastro.test', 'password' => 'clave-segura'])->assertRedirect('/ventanilla');

        // Un trámite con turnos no se borra (se desactiva); la propia cuenta tampoco.
        Turno::emitir($this->recepcion);
        $this->actingAs($admin)->from('/admin/tramites')->delete("/admin/tramites/{$this->recepcion->id}")->assertSessionHasErrors('eliminar');
        $this->from('/admin/usuarios')->delete("/admin/usuarios/{$admin->id}")->assertSessionHasErrors('eliminar');
        $this->assertNotNull($admin->fresh());
    }
}
