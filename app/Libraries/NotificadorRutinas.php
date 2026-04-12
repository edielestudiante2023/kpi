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

        // Usuarios con asignaciones activas
        $usuarios = $db->query("
            SELECT DISTINCT u.id_users, u.nombre_completo, u.correo
            FROM rutinas_asignaciones ra
            JOIN users u ON u.id_users = ra.id_users
            WHERE ra.activa = 1 AND u.activo = 1
            ORDER BY u.nombre_completo
        ")->getResultArray();

        foreach ($usuarios as $u) {
            // Contar actividades del usuario
            $total = $db->query("
                SELECT COUNT(*) as cnt
                FROM rutinas_asignaciones ra
                JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
                WHERE ra.id_users = ? AND ra.activa = 1 AND a.activa = 1
            ", [$u['id_users']])->getRow()->cnt;

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
