<?php

namespace App\Libraries;

use App\Models\RutinaAsignacionModel;
use App\Models\UserModel;

require_once ROOTPATH . 'vendor/autoload.php';

class NotificadorRutinas
{
    protected $fromEmail = 'notificacion.cycloidtalent@cycloidtalent.com';
    protected $fromName  = 'Rutinas Cycloid';

    /**
     * Envía un email diario a cada usuario que tenga rutinas asignadas.
     * Contiene un enlace tokenizado a la vista pública de checklist.
     */
    public function enviarRecordatoriosDiarios(?string $fechaManual = null): array
    {
        $resultado = [
            'enviados' => 0,
            'errores'  => 0,
            'omitidos' => 0,
            'fecha'    => null,
        ];

        $fecha = $fechaManual ?: date('Y-m-d');
        $resultado['fecha'] = $fecha;

        // Validar que sea día hábil (L-V)
        $dow = (int) date('N', strtotime($fecha));
        if ($dow > 5) {
            $resultado['omitidos'] = -1; // señal de fin de semana
            return $resultado;
        }

        $db = \Config\Database::connect();

        // Verificar que no sea festivo
        $esFestivo = $db->table('dias_festivos')->where('fecha', $fecha)->countAllResults() > 0;
        if ($esFestivo) {
            $resultado['omitidos'] = -2; // señal de festivo
            return $resultado;
        }

        // Usuarios con asignaciones activas
        $usuarios = $db->query("
            SELECT DISTINCT u.id_users, u.nombre_completo, u.correo
            FROM rutinas_asignaciones ra
            JOIN users u ON u.id_users = ra.id_users
            WHERE ra.activa = 1 AND u.activo = 1
            ORDER BY u.nombre_completo
        ")->getResultArray();

        // Calcular lunes y domingo de la semana ISO
        $ts = strtotime($fecha);
        $dow = (int) date('N', $ts);
        $lunes  = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days', $ts));
        $domingo = date('Y-m-d', strtotime('+' . (7 - $dow) . ' days', $ts));

        foreach ($usuarios as $u) {
            // Actividades pendientes "para hoy": excluye semanales con meta ya cumplida
            $rows = $db->query("
                SELECT a.id_actividad, a.frecuencia, a.meta_semanal,
                       (SELECT COUNT(DISTINCT fecha) FROM rutinas_registros
                        WHERE id_users = ra.id_users AND id_actividad = a.id_actividad
                          AND completada = 1 AND fecha BETWEEN ? AND ?) AS dias_semana
                FROM rutinas_asignaciones ra
                JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
                WHERE ra.id_users = ? AND ra.activa = 1 AND a.activa = 1
            ", [$lunes, $domingo, $u['id_users']])->getResultArray();

            $total = 0;
            foreach ($rows as $r) {
                if ($r['frecuencia'] === 'semanal') {
                    $meta = max(1, (int)($r['meta_semanal'] ?? 1));
                    if ((int)$r['dias_semana'] >= $meta) continue; // meta cumplida, no cuenta
                }
                $total++;
            }

            if ($total == 0) {
                $resultado['omitidos']++;
                continue;
            }

            $token = $this->generarToken((int)$u['id_users'], $fecha);
            $url   = rtrim(base_url(), '/') . "/rutinas/checklist/{$u['id_users']}/{$fecha}/{$token}";

            $html  = $this->generarHTML($u, $fecha, $url, (int)$total);
            $asunto = "Rutina diaria — " . date('d/m/Y', strtotime($fecha));

            if ($this->enviarEmail($u['correo'], $u['nombre_completo'], $asunto, $html)) {
                $resultado['enviados']++;
            } else {
                $resultado['errores']++;
            }
        }

        return $resultado;
    }

    private function generarToken(int $userId, string $fecha): string
    {
        return substr(hash('sha256', $userId . '|' . $fecha . '|rutinas2026'), 0, 24);
    }

    /**
     * Envia el resumen semanal cada lunes:
     * - Cumplimiento de la semana anterior (con detalle por actividad)
     * - Meta de la nueva semana
     */
    public function enviarResumenSemanal(?string $fechaManual = null): array
    {
        $resultado = ['enviados' => 0, 'errores' => 0, 'omitidos' => 0, 'fecha' => null];

        $hoy = $fechaManual ?: date('Y-m-d');
        $resultado['fecha'] = $hoy;

        // Solo lunes (1=lun .. 7=dom) salvo modo manual con cualquier fecha
        if (!$fechaManual) {
            $dow = (int) date('N', strtotime($hoy));
            if ($dow !== 1) {
                $resultado['omitidos'] = -1;
                return $resultado;
            }
        }

        // Rango: lunes anterior a domingo anterior
        $ts = strtotime($hoy);
        $lunesAnt   = date('Y-m-d', strtotime('-7 days', $ts));
        $domingoAnt = date('Y-m-d', strtotime('-1 day', $ts));
        $lunesNueva = date('Y-m-d', $ts);
        $domingoNueva = date('Y-m-d', strtotime('+6 days', $ts));

        $db = \Config\Database::connect();

        // Usuarios con asignaciones activas
        $usuarios = $db->query("
            SELECT DISTINCT u.id_users, u.nombre_completo, u.correo
            FROM rutinas_asignaciones ra
            JOIN users u ON u.id_users = ra.id_users
            WHERE ra.activa = 1 AND u.activo = 1
            ORDER BY u.nombre_completo
        ")->getResultArray();

        foreach ($usuarios as $u) {
            $detalle = $this->calcularDetalleSemanal($db, (int)$u['id_users'], $lunesAnt, $domingoAnt);
            $metaNueva = $this->calcularMetaSemanal($db, (int)$u['id_users']);

            if (empty($detalle['actividades']) && empty($metaNueva)) {
                $resultado['omitidos']++;
                continue;
            }

            $html = $this->generarHTMLSemanal($u, $lunesAnt, $domingoAnt, $lunesNueva, $domingoNueva, $detalle, $metaNueva);
            $asunto = "Resumen semanal de rutinas — " . date('d/m', strtotime($lunesAnt)) . ' al ' . date('d/m/Y', strtotime($domingoAnt));

            if ($this->enviarEmail($u['correo'], $u['nombre_completo'], $asunto, $html)) {
                $resultado['enviados']++;
            } else {
                $resultado['errores']++;
            }
        }

        return $resultado;
    }

    /**
     * Detalle de cumplimiento de un usuario en un rango (semana anterior)
     */
    private function calcularDetalleSemanal(\CodeIgniter\Database\BaseConnection $db, int $idUser, string $desde, string $hasta): array
    {
        // Días hábiles del rango (excluyendo festivos)
        $festivos = $db->table('dias_festivos')
            ->where('fecha >=', $desde)->where('fecha <=', $hasta)
            ->get()->getResultArray();
        $fechasFestivas = array_flip(array_column($festivos, 'fecha'));

        $diasHabiles = 0; $diasTotales = 0;
        $cursor = strtotime($desde); $fin = strtotime($hasta);
        while ($cursor <= $fin) {
            $f = date('Y-m-d', $cursor);
            if (!isset($fechasFestivas[$f])) {
                $diasTotales++;
                $dow = (int) date('N', $cursor);
                if ($dow <= 5) $diasHabiles++;
            }
            $cursor += 86400;
        }

        $rows = $db->query("
            SELECT a.id_actividad, a.nombre, a.categoria, a.frecuencia, a.meta_semanal,
                   (SELECT COUNT(DISTINCT fecha) FROM rutinas_registros
                    WHERE id_users = ? AND id_actividad = a.id_actividad
                      AND completada = 1 AND fecha BETWEEN ? AND ?) AS dias_done
            FROM rutinas_asignaciones ra
            JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
            WHERE ra.id_users = ? AND ra.activa = 1 AND a.activa = 1
            ORDER BY a.categoria, a.nombre
        ", [$idUser, $desde, $hasta, $idUser])->getResultArray();

        $actividades = [];
        $sumPct = 0; $cntPct = 0;
        foreach ($rows as $r) {
            $done = (int)$r['dias_done'];
            if ($r['frecuencia'] === 'semanal') {
                $meta = max(1, (int)($r['meta_semanal'] ?? 1));
                $pct  = (int) round((min($done, $meta) / $meta) * 100);
                $esp  = $meta;
            } elseif ($r['frecuencia'] === 'diaria') {
                $esp = $diasTotales;
                $pct = $esp > 0 ? (int) round(($done / $esp) * 100) : 0;
            } else { // L-V
                $esp = $diasHabiles;
                $pct = $esp > 0 ? (int) round(($done / $esp) * 100) : 0;
            }
            $pct = min(100, max(0, $pct));
            $actividades[] = [
                'nombre' => $r['nombre'],
                'categoria' => $r['categoria'] ?? 'General',
                'frecuencia' => $r['frecuencia'],
                'meta_semanal' => $r['meta_semanal'],
                'done' => $done,
                'esperados' => $esp,
                'pct' => $pct,
            ];
            $sumPct += $pct; $cntPct++;
        }

        return [
            'actividades' => $actividades,
            'promedio'    => $cntPct > 0 ? (int) round($sumPct / $cntPct) : 0,
            'dias_habiles' => $diasHabiles,
        ];
    }

    /**
     * Lista de actividades activas con su meta para la nueva semana
     */
    private function calcularMetaSemanal(\CodeIgniter\Database\BaseConnection $db, int $idUser): array
    {
        return $db->query("
            SELECT a.id_actividad, a.nombre, a.categoria, a.frecuencia, a.meta_semanal
            FROM rutinas_asignaciones ra
            JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
            WHERE ra.id_users = ? AND ra.activa = 1 AND a.activa = 1
            ORDER BY a.categoria, a.nombre
        ", [$idUser])->getResultArray();
    }

    private function generarHTMLSemanal(array $usuario, string $lunesAnt, string $domingoAnt, string $lunesNueva, string $domingoNueva, array $detalle, array $metaNueva): string
    {
        $rangoAnt = date('d/m', strtotime($lunesAnt)) . ' - ' . date('d/m/Y', strtotime($domingoAnt));
        $rangoNueva = date('d/m', strtotime($lunesNueva)) . ' - ' . date('d/m/Y', strtotime($domingoNueva));
        $promedio = $detalle['promedio'] ?? 0;
        $colorProm = $promedio >= 80 ? '#28a745' : ($promedio >= 50 ? '#ffc107' : '#dc3545');

        $filasAnterior = '';
        foreach ($detalle['actividades'] as $a) {
            $color = $a['pct'] >= 80 ? '#28a745' : ($a['pct'] >= 50 ? '#ffc107' : '#dc3545');
            $freqLabel = $a['frecuencia'] === 'semanal' ? 'meta '.($a['meta_semanal'] ?? 1).'/sem'
                       : ($a['frecuencia'] === 'diaria' ? 'diaria' : 'L-V');
            $filasAnterior .= "<tr>
                <td style='padding:6px;border:1px solid #dee2e6;font-size:13px;'>".htmlspecialchars($a['nombre'])."<br><small style='color:#6c757d;'>".htmlspecialchars($a['categoria'])." · {$freqLabel}</small></td>
                <td style='padding:6px;border:1px solid #dee2e6;text-align:center;font-size:13px;'>{$a['done']}/{$a['esperados']}</td>
                <td style='padding:6px;border:1px solid #dee2e6;text-align:center;font-size:13px;font-weight:bold;color:{$color};'>{$a['pct']}%</td>
            </tr>";
        }
        if (!$filasAnterior) {
            $filasAnterior = "<tr><td colspan='3' style='padding:10px;text-align:center;color:#6c757d;'>Sin actividades registradas la semana pasada.</td></tr>";
        }

        $filasNueva = '';
        foreach ($metaNueva as $a) {
            $freq = $a['frecuencia'];
            if ($freq === 'semanal') {
                $metaTxt = "🗓️ ".($a['meta_semanal'] ?? 1)." veces a la semana";
            } elseif ($freq === 'diaria') {
                $metaTxt = "📅 todos los días";
            } else {
                $metaTxt = "📆 lunes a viernes";
            }
            $filasNueva .= "<tr>
                <td style='padding:6px;border:1px solid #dee2e6;font-size:13px;'>".htmlspecialchars($a['nombre'])."<br><small style='color:#6c757d;'>".htmlspecialchars($a['categoria'] ?? 'General')."</small></td>
                <td style='padding:6px;border:1px solid #dee2e6;font-size:13px;'>{$metaTxt}</td>
            </tr>";
        }
        if (!$filasNueva) {
            $filasNueva = "<tr><td colspan='2' style='padding:10px;text-align:center;color:#6c757d;'>No tienes rutinas asignadas.</td></tr>";
        }

        return "
        <div style='font-family:Segoe UI,Arial,sans-serif;max-width:680px;margin:0 auto;'>
            <div style='background:#1c2437;padding:20px;text-align:center;border-radius:10px 10px 0 0;'>
                <h1 style='color:#bd9751;margin:0;font-size:20px;'>Resumen Semanal de Rutinas</h1>
                <p style='color:#adb5bd;margin:6px 0 0;font-size:13px;'>{$usuario['nombre_completo']}</p>
            </div>
            <div style='padding:25px;background:#f8f9fa;border-radius:0 0 10px 10px;'>
                <div style='text-align:center;background:#fff;padding:18px;border-radius:8px;border:1px solid #e9ecef;margin-bottom:18px;'>
                    <div style='font-size:13px;color:#6c757d;'>Promedio de cumplimiento ({$rangoAnt})</div>
                    <div style='font-size:38px;font-weight:bold;color:{$colorProm};margin-top:4px;'>{$promedio}%</div>
                </div>

                <h3 style='color:#1c2437;font-size:16px;margin:18px 0 8px;'>Cumplimiento semana anterior</h3>
                <table style='width:100%;border-collapse:collapse;background:#fff;'>
                    <thead>
                        <tr style='background:#1c2437;color:#fff;'>
                            <th style='padding:8px;text-align:left;font-size:12px;'>Actividad</th>
                            <th style='padding:8px;text-align:center;font-size:12px;'>Hecho/Esperado</th>
                            <th style='padding:8px;text-align:center;font-size:12px;'>%</th>
                        </tr>
                    </thead>
                    <tbody>{$filasAnterior}</tbody>
                </table>

                <h3 style='color:#1c2437;font-size:16px;margin:24px 0 8px;'>Meta para esta semana ({$rangoNueva})</h3>
                <table style='width:100%;border-collapse:collapse;background:#fff;'>
                    <thead>
                        <tr style='background:#1c2437;color:#fff;'>
                            <th style='padding:8px;text-align:left;font-size:12px;'>Actividad</th>
                            <th style='padding:8px;text-align:left;font-size:12px;'>Frecuencia</th>
                        </tr>
                    </thead>
                    <tbody>{$filasNueva}</tbody>
                </table>

                <p style='color:#6c757d;font-size:11px;margin-top:24px;text-align:center;'>
                    Recibirás cada mañana (L-V) un email con tu rutina del día. ¡Mucho éxito esta semana!
                </p>
                <p style='color:#999;font-size:11px;text-align:center;'>Rutinas de Trabajo — Cycloid Talent</p>
            </div>
        </div>";
    }

    private function generarHTML(array $usuario, string $fecha, string $url, int $totalAct): string
    {
        $fechaFmt = date('d/m/Y', strtotime($fecha));
        $diasSemana = ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'];
        $diaSemana  = $diasSemana[(int)date('w', strtotime($fecha))];

        return "
        <div style='font-family:Segoe UI,Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:#1c2437;padding:20px;text-align:center;border-radius:10px 10px 0 0;'>
                <h1 style='color:#bd9751;margin:0;font-size:20px;'>Rutina Diaria</h1>
                <p style='color:#adb5bd;margin:6px 0 0;font-size:13px;'>{$diaSemana} {$fechaFmt}</p>
            </div>
            <div style='padding:25px;background:#f8f9fa;border-radius:0 0 10px 10px;'>
                <p style='font-size:15px;color:#333;'>Hola <strong>{$usuario['nombre_completo']}</strong>,</p>
                <p style='font-size:14px;color:#555;'>
                    Tienes <strong>{$totalAct} actividades</strong> en tu rutina de hoy.
                    Marca las que vayas completando:
                </p>
                <div style='text-align:center;margin:24px 0;'>
                    <a href='{$url}' style='background:#bd9751;color:white;padding:14px 28px;
                       border-radius:8px;text-decoration:none;font-weight:bold;font-size:15px;
                       display:inline-block;'>
                        Completar Rutina
                    </a>
                </div>
                <p style='font-size:12px;color:#999;'>Enlace directo: {$url}</p>
                <p style='color:#999;font-size:11px;margin-top:16px;'>Rutinas de Trabajo — Cycloid Talent</p>
            </div>
        </div>";
    }

    private function enviarEmail(string $emailDestino, string $nombreDestino, string $asunto, string $html): bool
    {
        try {
            $apiKey = env('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                log_message('error', 'NotificadorRutinas: SENDGRID_API_KEY no configurada');
                return false;
            }

            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($asunto);
            $email->addTo($emailDestino, $nombreDestino ?: $emailDestino);
            $email->addContent("text/html", $html);

            // Deshabilitar click tracking para no reescribir la URL
            $email->setClickTracking(false, false);

            $sendgrid = new \SendGrid($apiKey);
            $response = $sendgrid->send($email);
            $statusCode = $response->statusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                log_message('info', "NotificadorRutinas: Email enviado a {$emailDestino} — {$asunto}");
                return true;
            } else {
                log_message('error', "NotificadorRutinas: Error SendGrid - Status: {$statusCode} - Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'NotificadorRutinas: Excepcion - ' . $e->getMessage());
            return false;
        }
    }
}
