<?php

namespace Database\Seeders;

use App\Models\Tramite;
use App\Models\User;
use App\Models\Ventanilla;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Requisitos de referencia, recopilados de trámites publicados por varios GAD municipales
        // en gob.ec. Cada cantón tiene los suyos: revisarlos y corregirlos en Administración > Trámites.
        $cedula = 'Copia de cédula y certificado de votación del propietario';
        $noAdeudar = 'Certificado de no adeudar al Municipio';
        $predial = 'Comprobante de pago del impuesto predial del año en curso';
        $escritura = 'Copia de la escritura inscrita en el Registro de la Propiedad';
        $gravamen = 'Certificado de gravámenes actualizado (Registro de la Propiedad)';
        $solicitud = 'Solicitud dirigida al Director de Avalúos y Catastros (formulario o especie valorada)';
        $plano = 'Levantamiento planimétrico georreferenciado, impreso y en digital (CD/USB), firmado por un profesional';

        $recepcion = [
            ['Certificado de avalúos y catastro', 'Certifica el avalúo y los datos catastrales de un predio.',
                [$solicitud, $cedula, $escritura, $predial, $noAdeudar, 'Comprobante de pago de la tasa del certificado']],
            ['Transferencia de dominio', 'Compraventa, donación, herencia o partición de un predio.',
                ['Formulario de transferencia de dominio', 'Minuta notarial', 'Copia de cédula y certificado de votación de compradores y vendedores', $escritura, $gravamen, $predial, "$noAdeudar (compradores y vendedores)", 'Posesión efectiva inscrita, si se trata de herederos']],
            ['Ingreso de predio al catastro', 'Registro de un predio por primera vez y asignación de clave catastral.',
                [$solicitud, 'Formulario de ingreso al catastro', $cedula, $escritura, $gravamen, $plano, $noAdeudar]],
            ['Actualización de datos catastrales', 'Cambio de propietario, área, linderos o construcción en la ficha catastral.',
                [$solicitud, $cedula, $escritura, $gravamen, $predial, 'Documento que respalde el cambio (escritura, planos aprobados, permiso de construcción)']],
            ['Aprobación de levantamiento planimétrico', 'Revisión y aprobación de planos de un predio.',
                [$solicitud, $cedula, $escritura, $gravamen, $plano, $predial, $noAdeudar]],
            ['Fraccionamiento o subdivisión de lote', 'División de un predio en dos o más lotes.',
                [$solicitud, $cedula, $escritura, $gravamen, 'Informe de regulación / línea de fábrica del predio', 'Planos del fraccionamiento (estado actual y propuesta) impresos y en digital, firmados por un profesional', $predial, $noAdeudar]],
            ['Unificación de lotes', 'Integración de dos o más predios colindantes en uno solo.',
                [$solicitud, $cedula, 'Copia de las escrituras inscritas de todos los lotes', 'Certificado de gravámenes de cada lote', 'Plano de unificación impreso y en digital, firmado por un profesional', "$predial (de cada lote)", $noAdeudar]],
            ['Declaratoria de propiedad horizontal', 'Edificios, conjuntos habitacionales y construcciones por pisos o unidades.',
                [$solicitud, $cedula, $escritura, $gravamen, 'Planos arquitectónicos aprobados y permiso de construcción', 'Cuadro de áreas, alícuotas y linderos firmado por un profesional', 'Reglamento interno de copropiedad', $predial, $noAdeudar]],
            ['Rectificación de linderos, medidas o excedentes', 'Corrección de áreas, linderos y regularización de excedentes o diferencias.',
                [$solicitud, $cedula, $escritura, $gravamen, $plano, $predial, $noAdeudar]],
            ['Informe de uso de suelo', 'Indica los usos permitidos y la regulación del predio.',
                [$solicitud, $cedula, $escritura, $predial, 'Croquis de ubicación del predio', 'Comprobante de pago de la tasa']],
            ['Certificado de bienes raíces', 'Certifica si una persona posee o no posee predios en el cantón.',
                [$solicitud, 'Copia de cédula y certificado de votación del solicitante', $noAdeudar, 'Comprobante de pago de la tasa']],
            ['Reclamo o revisión de avalúo', 'Solicitud de inspección y revisión del avalúo del predio.',
                [$solicitud, $cedula, $escritura, $predial, 'Fotografías o documentos que sustenten el reclamo']],
        ];

        $orden = 0;
        foreach ($recepcion as [$nombre, $descripcion, $requisitos]) {
            Tramite::create([
                'nombre' => $nombre, 'descripcion' => $descripcion, 'categoria' => 'recepcion',
                'requisitos' => implode("\n", $requisitos), 'hora_limite' => '12:00', 'orden' => ++$orden,
            ]);
        }

        Tramite::create([
            'nombre' => 'Retiro de documentos tramitados', 'descripcion' => 'Certificados, informes, planos aprobados y carpetas observadas.',
            'categoria' => 'entrega', 'orden' => ++$orden,
            'requisitos' => "Comprobante o número de ingreso del trámite\nCédula de quien retira\nAutorización simple y copia de cédula del titular, si retira otra persona",
        ]);
        Tramite::create([
            'nombre' => 'Información y consultas', 'descripcion' => 'Dudas sobre requisitos o sobre el estado de un trámite.',
            'categoria' => 'informacion', 'orden' => ++$orden,
            'requisitos' => "Cédula\nNúmero de trámite o clave catastral, si los tiene",
        ]);

        $v1 = Ventanilla::create(['nombre' => 'Ventanilla 1']);
        $v2 = Ventanilla::create(['nombre' => 'Ventanilla 2']);
        Ventanilla::create(['nombre' => 'Ventanilla 3', 'categoria' => 'entrega']);

        // Cuentas iniciales de prueba: cambiar las claves en Administración > Usuarios antes de usar en producción.
        User::create(['name' => 'Administrador', 'email' => 'admin@catastro.test', 'password' => 'catastro2026', 'rol' => 'admin']);
        User::create(['name' => 'Operador 1', 'email' => 'operador1@catastro.test', 'password' => 'catastro2026', 'rol' => 'operador', 'ventanilla_id' => $v1->id]);
        User::create(['name' => 'Operador 2', 'email' => 'operador2@catastro.test', 'password' => 'catastro2026', 'rol' => 'operador', 'ventanilla_id' => $v2->id]);
    }
}
