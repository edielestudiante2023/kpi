<?php

namespace App\Libraries;

require_once ROOTPATH . 'vendor/autoload.php';

/**
 * NotificadorCumpleanos
 *
 * Envía recordatorio diario a TODOS los usuarios activos MENOS al cumpleañero,
 * comenzando 30 días antes del cumpleaños, para organizar la celebración.
 *
 * Se puede silenciar (cuando ya cuadraron la fiesta) y se reactiva solo al
 * siguiente año vía la columna users.cumple_silenciado_hasta.
 */
class NotificadorCumpleanos
{
    private string $fromEmail = 'notificacion.cycloidtalent@cycloidtalent.com';
    private string $fromName  = 'Cumpleanos Cycloid';
    private int    $diasAviso = 30;

    /**
     * Genera token para silenciar desde el email (sin login)
     */
    public function generarTokenSilenciar(int $userId, int $anio): string
    {
        return substr(hash('sha256', $userId . '|' . $anio . '|cumple-silenciar-2026'), 0, 24);
    }

    /**
     * Ejecuta el recordatorio diario.
     */
    public function ejecutar(?string $fechaManual = null): array
    {
        $resultado = ['cumpleaneros' => 0, 'emails' => 0, 'errores' => 0, 'silenciados' => 0, 'detalle' => []];

        $hoy = $fechaManual ?: date('Y-m-d');
        $hoyTs = strtotime($hoy);
        $anioActual = (int) date('Y', $hoyTs);

        $db = \Config\Database::connect();

        // Usuarios activos con fecha de nacimiento
        $usuarios = $db->table('users')
            ->select('id_users, nombre_completo, correo, fecha_nacimiento, cumple_silenciado_hasta')
            ->where('activo', 1)
            ->where('fecha_nacimiento IS NOT NULL')
            ->get()->getResultArray();

        // Todos los activos (destinatarios potenciales)
        $todos = $db->table('users')
            ->select('id_users, nombre_completo, correo')
            ->where('activo', 1)
            ->where('correo IS NOT NULL')
            ->where("correo != ''")
            ->get()->getResultArray();

        foreach ($usuarios as $cumple) {
            $proximo = $this->proximoCumple($cumple['fecha_nacimiento'], $hoyTs);
            if ($proximo === null) continue;

            $diasFaltan = (int) floor(($proximo - $hoyTs) / 86400);

            // Solo dentro de la ventana de aviso (0 a diasAviso días antes)
            if ($diasFaltan < 0 || $diasFaltan > $this->diasAviso) continue;

            $resultado['cumpleaneros']++;

            // ¿Silenciado? cumple_silenciado_hasta >= hoy
            if (!empty($cumple['cumple_silenciado_hasta'])
                && $cumple['cumple_silenciado_hasta'] >= $hoy) {
                $resultado['silenciados']++;
                $resultado['detalle'][] = "{$cumple['nombre_completo']}: silenciado hasta {$cumple['cumple_silenciado_hasta']} (no se envia)";
                continue;
            }

            // Fecha de cumple de este ciclo (para el token y el silencio)
            $fechaCumpleAnio = (int) date('Y', $proximo);
            $token = $this->generarTokenSilenciar((int)$cumple['id_users'], $fechaCumpleAnio);
            $urlSilenciar = rtrim(base_url(), '/') . "/cumpleanos/silenciar/{$cumple['id_users']}/{$fechaCumpleAnio}/{$token}";

            $edad = $fechaCumpleAnio - (int) date('Y', strtotime($cumple['fecha_nacimiento']));
            $fechaFmt = $this->formatoFecha($proximo);

            // Enviar a todos menos al cumpleañero
            foreach ($todos as $dest) {
                if ((int)$dest['id_users'] === (int)$cumple['id_users']) continue;
                if (empty($dest['correo'])) continue;

                $html = $this->generarHTML($cumple['nombre_completo'], $fechaFmt, $diasFaltan, $edad, $dest['nombre_completo'], $urlSilenciar);
                $asunto = $diasFaltan === 0
                    ? "Hoy es el cumpleanos de {$cumple['nombre_completo']}!"
                    : "Faltan {$diasFaltan} dias para el cumpleanos de {$cumple['nombre_completo']}";

                if ($this->enviarEmail($dest['correo'], $dest['nombre_completo'], $asunto, $html)) {
                    $resultado['emails']++;
                } else {
                    $resultado['errores']++;
                }
            }
            $resultado['detalle'][] = "{$cumple['nombre_completo']}: cumple {$fechaFmt} (faltan {$diasFaltan} dias) - recordatorio enviado";
        }

        return $resultado;
    }

    /**
     * Timestamp del próximo cumpleaños (este año o el siguiente) a partir de hoy.
     */
    private function proximoCumple(string $fechaNacimiento, int $hoyTs): ?int
    {
        $mes = (int) date('n', strtotime($fechaNacimiento));
        $dia = (int) date('j', strtotime($fechaNacimiento));
        if (!$mes || !$dia) return null;

        $anioHoy = (int) date('Y', $hoyTs);
        // Manejar 29-feb: si el año no es bisiesto, usar 28-feb
        $diaAjustado = $dia;
        if ($mes === 2 && $dia === 29 && !checkdate(2, 29, $anioHoy)) {
            $diaAjustado = 28;
        }
        $esteAnio = strtotime(sprintf('%04d-%02d-%02d', $anioHoy, $mes, $diaAjustado));

        // Si ya pasó (antes de hoy), tomar el del próximo año
        if ($esteAnio < $hoyTs) {
            $anioSig = $anioHoy + 1;
            $diaSig = $dia;
            if ($mes === 2 && $dia === 29 && !checkdate(2, 29, $anioSig)) $diaSig = 28;
            return strtotime(sprintf('%04d-%02d-%02d', $anioSig, $mes, $diaSig));
        }
        return $esteAnio;
    }

    private function formatoFecha(int $ts): string
    {
        $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)];
    }

    private function generarHTML(string $cumpleNombre, string $fechaFmt, int $diasFaltan, int $edad, string $destNombre, string $urlSilenciar): string
    {
        $titulo = $diasFaltan === 0
            ? "¡Hoy es el cumpleanos de {$cumpleNombre}!"
            : "Se acerca un cumpleanos";
        $mensajeDias = $diasFaltan === 0
            ? "<strong>Hoy</strong> celebramos a {$cumpleNombre}."
            : "Faltan <strong>{$diasFaltan} dia(s)</strong> para el cumpleanos de <strong>{$cumpleNombre}</strong> ({$fechaFmt}).";

        return "
        <div style='font-family:Segoe UI,Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:#1c2437;padding:24px;text-align:center;border-radius:10px 10px 0 0;'>
                <div style='font-size:42px;'>🎂</div>
                <h1 style='color:#bd9751;margin:8px 0 0;font-size:21px;'>{$titulo}</h1>
            </div>
            <div style='padding:25px;background:#f8f9fa;border-radius:0 0 10px 10px;'>
                <p style='font-size:15px;color:#333;'>Hola <strong>{$destNombre}</strong>,</p>
                <p style='font-size:15px;color:#555;'>{$mensajeDias}</p>
                <p style='font-size:14px;color:#555;'>
                    Es un buen momento para organizar la celebracion entre todos.
                    Coordinemos el detalle, la torta y la sorpresa. 🎉
                </p>
                <div style='background:#fff;border:1px solid #e9ecef;border-radius:8px;padding:14px;margin:18px 0;text-align:center;'>
                    <div style='font-size:13px;color:#6c757d;'>Cumpleanos</div>
                    <div style='font-size:18px;font-weight:bold;color:#1c2437;'>{$cumpleNombre}</div>
                    <div style='font-size:14px;color:#bd9751;'>{$fechaFmt}</div>
                </div>
                <div style='text-align:center;margin:24px 0;'>
                    <a href='{$urlSilenciar}' style='background:#6c757d;color:white;padding:12px 22px;
                       border-radius:8px;text-decoration:none;font-weight:bold;font-size:14px;display:inline-block;'>
                        🔕 Ya cuadramos la celebracion (dejar de recibir este recordatorio)
                    </a>
                </div>
                <p style='font-size:11px;color:#999;text-align:center;'>
                    Al hacer clic, se detiene el recordatorio de este cumpleanos para todo el equipo.
                    Volvera a activarse automaticamente el proximo ano.
                </p>
                <p style='color:#999;font-size:11px;text-align:center;margin-top:14px;'>Cycloid Talent</p>
            </div>
        </div>";
    }

    private function enviarEmail(string $emailDestino, string $nombreDestino, string $asunto, string $html): bool
    {
        try {
            $apiKey = env('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                log_message('error', 'NotificadorCumpleanos: SENDGRID_API_KEY no configurada');
                return false;
            }

            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($asunto);
            $email->addTo($emailDestino, $nombreDestino ?: $emailDestino);
            $email->addContent("text/html", $html);
            $email->setClickTracking(false, false);

            $sendgrid = new \SendGrid($apiKey);
            $response = $sendgrid->send($email);
            $statusCode = $response->statusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return true;
            }
            log_message('error', "NotificadorCumpleanos: SendGrid status {$statusCode} - " . $response->body());
            return false;
        } catch (\Exception $e) {
            log_message('error', 'NotificadorCumpleanos: Excepcion - ' . $e->getMessage());
            return false;
        }
    }
}
