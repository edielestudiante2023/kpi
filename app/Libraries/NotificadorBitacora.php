<?php

namespace App\Libraries;

use App\Models\UserModel;
use App\Models\BitacoraActividadModel;
use App\Models\DiaFestivoModel;
use App\Models\LiquidacionModel;

require_once ROOTPATH . 'vendor/autoload.php';

class NotificadorBitacora
{
    protected $userModel;
    protected $bitacoraModel;
    protected $fromEmail = 'notificacion.cycloidtalent@cycloidtalent.com';
    protected $fromName  = 'Bitácora Cycloid';

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->bitacoraModel = new BitacoraActividadModel();
    }

    /**
     * Envía todos los reportes del día laboral anterior.
     * Si hoy es lunes → reporta viernes. Si es sáb/dom → no envía.
     */
    public function enviarTodosLosReportes(?string $fechaManual = null): array
    {
        $resultado = [
            'enviados'         => 0,
            'errores'          => 0,
            'sin_actividades'  => 0,
            'fecha_reportada'  => null,
        ];

        // Fecha manual o día anterior por defecto
        $fechaReporte = $fechaManual ?: date('Y-m-d', strtotime('-1 day'));

        $resultado['fecha_reportada'] = $fechaReporte;

        // Obtener emails en copia desde .env (Diana, Edison, etc.)
        $emailsConfig = env('BITACORA_REPORT_EMAILS', '');
        $copias = array_filter(array_map('trim', explode(',', $emailsConfig)));

        // Obtener usuarios con bitácora habilitada
        $usuarios = $this->userModel
            ->where('activo', 1)
            ->where('bitacora_habilitada', 1)
            ->findAll();

        foreach ($usuarios as $usuario) {
            $actividades = $this->bitacoraModel->getActividadesDelDia(
                (int) $usuario['id_users'],
                $fechaReporte
            );

            // Si no hay actividades, omitir
            if (empty($actividades)) {
                $resultado['sin_actividades']++;
                continue;
            }

            $totalMinutos = $this->bitacoraModel->getTotalMinutosDia(
                (int) $usuario['id_users'],
                $fechaReporte
            );

            $progresoQuincenal = $this->calcularProgresoQuincenal($usuario, $fechaReporte . ' 23:59:59');
            $html = $this->generarHTMLReporte($usuario, $actividades, $totalMinutos, $fechaReporte, $progresoQuincenal);
            $asunto = "Bitácora de {$usuario['nombre_completo']} — " . date('d/m/Y', strtotime($fechaReporte));

            // Enviar PARA el usuario, con CC a Diana/Edison
            if ($this->enviarEmail($usuario['correo'], $usuario['nombre_completo'], $asunto, $html, $copias)) {
                $resultado['enviados']++;
            } else {
                $resultado['errores']++;
            }
        }

        return $resultado;
    }

    /**
     * Calcula el progreso quincenal de un usuario
     */
    protected function calcularProgresoQuincenal(array $usuario, ?string $hasta = null): ?array
    {
        try {
            $liqModel = new LiquidacionModel();
            $festivoModel = new DiaFestivoModel();

            $ultima = $liqModel->getUltimaLiquidacion();
            $fechaInicio = $ultima ? $ultima['fecha_corte'] : env('BITACORA_PRIMERA_QUINCENA', '');
            if (empty($fechaInicio)) return null;

            $ahora = $hasta ?? date('Y-m-d H:i:s');

            // Días hábiles transcurridos (solo para mostrar avance)
            $diasTranscurridos = $festivoModel->contarDiasHabiles($fechaInicio, $ahora);

            // Meta fija: días hábiles de la quincena completa (14 días calendario = 2 semanas)
            $fechaFinQuincena = date('Y-m-d', strtotime(substr($fechaInicio, 0, 10) . ' +14 days'));
            $diasHabilesMeta  = $festivoModel->contarDiasHabiles($fechaInicio, $fechaFinQuincena);
            if ($diasHabilesMeta <= 0) return null;

            $totalMin = $this->bitacoraModel->getTotalMinutosRango(
                (int) $usuario['id_users'], $fechaInicio, $ahora
            );
            $horasTrabajadas = round($totalMin / 60, 2);

            $jornada = $usuario['jornada'] ?? 'completa';
            if ($jornada === 'media') {
                $horasMeta = round($diasHabilesMeta * 4 * 0.90, 2);
            } else {
                $horasMeta = round($diasHabilesMeta * 8 * 0.80, 2);
            }

            $porcentaje = $horasMeta > 0 ? round(($horasTrabajadas / $horasMeta) * 100, 1) : 0;

            $diasDetalle = $this->bitacoraModel->getResumenDiarioRango(
                (int) $usuario['id_users'], $fechaInicio, $ahora
            );

            return [
                'fecha_inicio'       => $fechaInicio,
                'dias_habiles'       => $diasHabilesMeta,
                'dias_transcurridos' => $diasTranscurridos,
                'horas_trabajadas'   => $horasTrabajadas,
                'horas_meta'         => $horasMeta,
                'porcentaje'         => $porcentaje,
                'jornada'            => $jornada,
                'dias_detalle'       => $diasDetalle,
            ];
        } catch (\Exception $e) {
            log_message('error', 'NotificadorBitacora: Error progreso quincenal - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Genera el HTML del reporte diario de un usuario
     */
    protected function generarHTMLReporte(array $usuario, array $actividades, float $totalMinutos, string $fecha, ?array $progreso = null): string
    {
        $totalHoras = $this->formatMinutosHoras($totalMinutos);
        $fechaFormateada = date('d/m/Y', strtotime($fecha));

        $diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        $diaNombre = $diasSemana[(int) date('w', strtotime($fecha))];

        // Filas de la tabla
        $filasHTML = '';
        foreach ($actividades as $act) {
            $horaInicio = date('h:i A', strtotime($act['hora_inicio']));
            $horaFin = $act['hora_fin'] ? date('h:i A', strtotime($act['hora_fin'])) : 'En progreso';
            $duracion = $act['duracion_minutos'] ? $this->formatMinutosHoras((float) $act['duracion_minutos']) : '-';
            $ccNombre = $act['centro_costo_nombre'] ?? '-';

            $filasHTML .= "
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: center;'>{$act['numero_actividad']}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($act['descripcion']) . "</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($ccNombre) . "</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: center;'>{$horaInicio}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: center;'>{$horaFin}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: center; font-weight: bold;'>{$duracion}</td>
                </tr>";
        }

        return "
        <div style='font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto;'>
            <div style='background: #2c3e50; padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 22px;'>Reporte de Bitácora</h1>
                <p style='color: rgba(255,255,255,0.7); margin: 5px 0 0 0; font-size: 14px;'>{$diaNombre} {$fechaFormateada}</p>
            </div>

            <div style='padding: 25px; background: #f8f9fa;'>
                <div style='background: white; border-radius: 8px; padding: 15px; margin-bottom: 20px;'>
                    <table style='width: 100%; font-size: 14px;'>
                        <tr>
                            <td style='color: #6c757d;'>Usuario:</td>
                            <td style='font-weight: bold;'>{$usuario['nombre_completo']}</td>
                        </tr>
                        <tr>
                            <td style='color: #6c757d;'>Cargo:</td>
                            <td>{$usuario['cargo']}</td>
                        </tr>
                        <tr>
                            <td style='color: #6c757d;'>Total trabajado:</td>
                            <td style='font-weight: bold; color: #198754; font-size: 16px;'>{$totalHoras}</td>
                        </tr>
                        <tr>
                            <td style='color: #6c757d;'>Actividades:</td>
                            <td>" . count($actividades) . "</td>
                        </tr>
                    </table>
                </div>

                <table style='width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; font-size: 13px;'>
                    <thead>
                        <tr style='background: #2c3e50; color: white;'>
                            <th style='padding: 10px; text-align: center;'>#</th>
                            <th style='padding: 10px;'>Descripción</th>
                            <th style='padding: 10px;'>Centro Costo</th>
                            <th style='padding: 10px; text-align: center;'>Inicio</th>
                            <th style='padding: 10px; text-align: center;'>Fin</th>
                            <th style='padding: 10px; text-align: center;'>Duración</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$filasHTML}
                    </tbody>
                    <tfoot>
                        <tr style='background: #e9ecef;'>
                            <td colspan='5' style='padding: 10px; text-align: right; font-weight: bold;'>TOTAL:</td>
                            <td style='padding: 10px; text-align: center; font-weight: bold; color: #198754; font-size: 15px;'>{$totalHoras}</td>
                        </tr>
                    </tfoot>
                </table>

                {$this->generarSeccionProgresoQuincenal($progreso)}
            </div>

            <div style='padding: 15px; background: #e9ecef; text-align: center; font-size: 11px; color: #6c757d;'>
                <p style='margin: 0;'>Reporte generado automáticamente por Bitácora Cycloid</p>
            </div>
        </div>";
    }

    /**
     * Genera la sección HTML de progreso quincenal para el email diario
     */
    protected function generarSeccionProgresoQuincenal(?array $progreso): string
    {
        if (!$progreso) return '';

        $color = '#dc3545';
        if ($progreso['porcentaje'] >= 100) $color = '#198754';
        elseif ($progreso['porcentaje'] >= 80) $color = '#ffc107';

        $barWidth = min($progreso['porcentaje'], 100);
        $desde = date('d/m/Y', strtotime($progreso['fecha_inicio']));
        $jornadaLabel = $progreso['jornada'] === 'media' ? 'Media jornada' : 'Jornada completa';

        return "
                <div style='background: white; border-radius: 8px; padding: 15px; margin-top: 20px;'>
                    <h3 style='margin: 0 0 10px 0; font-size: 15px; color: #2c3e50;'>Progreso Quincenal</h3>
                    <table style='width: 100%; font-size: 13px; margin-bottom: 10px;'>
                        <tr>
                            <td style='color: #6c757d;'>Periodo desde:</td>
                            <td>{$desde} — Hoy</td>
                        </tr>
                        <tr>
                            <td style='color: #6c757d;'>Días hábiles:</td>
                            <td>{$progreso['dias_transcurridos']} de {$progreso['dias_habiles']} ({$jornadaLabel})</td>
                        </tr>
                        <tr>
                            <td style='color: #6c757d;'>Horas acumuladas:</td>
                            <td style='font-weight: bold;'>{$progreso['horas_trabajadas']}h / {$progreso['horas_meta']}h meta</td>
                        </tr>
                    </table>
                    <div style='background: #e9ecef; border-radius: 10px; height: 24px; overflow: hidden;'>
                        <div style='background: {$color}; height: 100%; width: {$barWidth}%; border-radius: 10px; text-align: center; color: white; font-size: 12px; font-weight: bold; line-height: 24px;'>
                            {$progreso['porcentaje']}%
                        </div>
                    </div>
                    {$this->generarTablaDetalleDias($progreso['dias_detalle'] ?? [])}
                </div>";
    }

    /**
     * Genera tabla HTML con el detalle de horas por día de la quincena
     */
    protected function generarTablaDetalleDias(array $dias): string
    {
        if (empty($dias)) return '';

        $diasSemana = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        $filas = '';
        foreach ($dias as $dia) {
            $nombreDia = $diasSemana[(int) date('w', strtotime($dia['fecha']))];
            $fechaFmt  = date('d/m', strtotime($dia['fecha']));
            $horas     = $this->formatMinutosHoras((float) $dia['total_minutos']);
            $filas .= "
                        <tr>
                            <td style='padding: 5px 8px; color: #6c757d; font-size: 12px;'>{$nombreDia}</td>
                            <td style='padding: 5px 8px; font-size: 12px;'>{$fechaFmt}</td>
                            <td style='padding: 5px 8px; text-align: right; font-weight: bold; font-size: 12px;'>{$horas}</td>
                        </tr>";
        }

        return "
                    <table style='width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 12px;'>
                        <thead>
                            <tr style='background: #f1f3f5;'>
                                <th style='padding: 6px 8px; text-align: left; color: #495057; font-weight: 600;'>Día</th>
                                <th style='padding: 6px 8px; text-align: left; color: #495057; font-weight: 600;'>Fecha</th>
                                <th style='padding: 6px 8px; text-align: right; color: #495057; font-weight: 600;'>Horas</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$filas}
                        </tbody>
                    </table>";
    }

    /**
     * Formatea minutos a texto legible "Xh Ymin"
     */
    protected function formatMinutosHoras(float $min): string
    {
        $h = floor($min / 60);
        $m = round($min - ($h * 60));
        if ($h > 0) return $h . 'h ' . $m . 'min';
        return $m . ' min';
    }

    /**
     * Envía email via SendGrid
     */
    public function enviarEmail(string $emailDestino, string $nombreDestino, string $asunto, string $contenidoHTML, array $copias = []): bool
    {
        try {
            $apiKey = env('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                log_message('error', 'NotificadorBitacora: SENDGRID_API_KEY no configurada');
                return false;
            }

            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($asunto);
            $email->addTo($emailDestino, $nombreDestino ?: $emailDestino);

            // Agregar CC (copia)
            foreach ($copias as $cc) {
                // No duplicar si el usuario ya es uno de los CC
                if (strtolower(trim($cc)) !== strtolower(trim($emailDestino))) {
                    $email->addCc($cc);
                }
            }

            $email->addContent("text/html", $contenidoHTML);

            $sendgrid = new \SendGrid($apiKey);
            $response = $sendgrid->send($email);
            $statusCode = $response->statusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                log_message('info', "NotificadorBitacora: Email enviado a {$emailDestino} (CC: " . implode(', ', $copias) . ") - {$asunto}");
                return true;
            } else {
                log_message('error', "NotificadorBitacora: Error SendGrid - Status: {$statusCode} - Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'NotificadorBitacora: Excepción - ' . $e->getMessage());
            return false;
        }
    }
}
