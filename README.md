# Sistema de turnos · Catastros y Avalúos

Turnero de ventanilla para la Dirección de Catastros y Avalúos (GAD Municipal). Laravel 13 + PostgreSQL.

## Arrancar

```bash
bash iniciar.sh
```

Abre en `http://localhost:8000` (o `http://IP-DEL-EQUIPO:8000` desde otros equipos de la red).

| Pantalla | Dirección | Para quién |
|---|---|---|
| Kiosco | `/?kiosco=1` | Ciudadano: elige trámite, ve requisitos, saca turno |
| Sala de espera | `/pantalla` | TV: turno llamado, con sonido y voz |
| Ventanilla | `/ventanilla` | Operador: llamar, volver a llamar, finalizar |
| Administración | `/admin` | Trámites, requisitos, horarios, ventanillas, usuarios, resumen del día |

**Equipo del kiosco**: ábralo una vez con `/?kiosco=1`. Ese navegador queda en modo kiosco: tras un minuto
sin que nadie toque la pantalla vuelve solo al inicio (avisa 15 segundos antes), para que el siguiente
ciudadano no vea el ticket ni la cédula del anterior. Se quita con `/?kiosco=0`. En los celulares no se activa.

Las cuentas iniciales están en `database/seeders/DatabaseSeeder.php`. Cámbielas antes de usar el sistema con público.

## Reglas

- Cada trámite tiene su hora límite (por defecto, recepción de carpetas hasta las 12:00) y cada tipo un cupo
  diario (recepción: 32; se cambia en Administración > Trámites). Pasada la hora o lleno el cupo, el kiosco
  entrega el turno para el siguiente día hábil con cupo, hasta 5 días hábiles adelante. No conoce feriados.
- Todo trámite cuyo tipo tiene cupo pide cédula (se valida el dígito verificador), sea para hoy o para otro día,
  y cada cédula puede tener un solo turno pendiente. Los tipos sin cupo (entregas, consultas) no la piden.
- El ticket muestra la cédula y se puede imprimir o guardar como imagen (PNG) en el celular. Su enlace
  usa un código secreto, no el número del turno, para que nadie pueda ver los tickets de otros.
- Quien perdió el ticket lo recupera en `/recuperar` (enlace en el kiosco) con su cédula. Solo se recuperan
  turnos pendientes, y hay un límite de 10 intentos por minuto para que nadie pruebe cédulas al azar.
- Los turnos se numeran por tipo y por día: `R-001` recepción, `E-001` entrega, `I-001` información.
- La atención preferente pasa primero; después, por orden de llegada.
- Una ventanilla puede atender todo o solo un tipo (por ejemplo, solo entregas).

## Instalar en otro equipo

Requiere PHP 8.3+, Composer y PostgreSQL con la extensión `pdo_pgsql`:

```bash
sudo apt install postgresql php8.3-pgsql
composer install
cp .env.example .env && php artisan key:generate   # ajustar DB_* en .env
php artisan migrate --seed
php artisan test                                    # usa la base tickets_test
```

En este equipo PostgreSQL no está instalado en el sistema: `entorno.sh` usa una copia local en
`~/.local/share/tickets-pg`. Antes de usar `php artisan` a mano: `source entorno.sh`.

Los requisitos cargados son de referencia (tomados de trámites publicados por varios municipios en gob.ec):
hay que validarlos con la Dirección y corregirlos en Administración > Trámites.
