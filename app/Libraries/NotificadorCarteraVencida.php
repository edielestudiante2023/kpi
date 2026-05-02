<?php

namespace App\Libraries;

/**
 * NotificadorCarteraVencida
 *
 * Envía emails cada dos jueves a clientes con facturas vencidas (>30 días sin pago).
 * Busca el email del cliente por NIT en 3 bases externas.
 */
class NotificadorCarteraVencida
{
    private string $fromEmail = 'notificacion.cycloidtalent@cycloidtalent.com';
    private string $fromName  = 'Kpi Cycloid - Cartera';
    private string $ccDiana   = 'diana.cuestas@cycloidtalent.com';
    private string $ccEdison  = 'head.consultant.cycloidtalent@gmail.com';

    private string $extHost;
    private int    $extPort;
    private string $extUser;
    private string $extPass;

    public function __construct()
    {
        $this->extHost = env('EXT_DB_HOST', '127.0.0.1');
        $this->extPort = (int) env('EXT_DB_PORT', 3306);
        $this->extUser = env('EXT_DB_USER', 'root');
        $this->extPass = env('EXT_DB_PASS', '');
    }

    /**
     * Ejecutar notificaciones de cartera vencida.
     * Solo envía cada dos jueves (semanas pares), salvo que se fuerce.
     */
    public function ejecutar(bool $forzar = false): array
    {
        $resultado = ['enviados' => 0, 'sin_email' => 0, 'errores' => 0, 'detalle' => []];

        if (!$forzar) {
            // Solo jueves
            if (date('N') !== '4') {
                $resultado['detalle'][] = 'Hoy no es jueves. No se envían notificaciones.';
                return $resultado;
            }
            // Solo semanas pares (cada dos jueves)
            $semana = (int) date('W');
            if ($semana % 2 !== 0) {
                $resultado['detalle'][] = "Hoy es jueves pero semana impar ({$semana}). Se envía solo en semanas pares.";
                return $resultado;
            }
        }

        $db = \Config\Database::connect();

        // Facturas vencidas: no pagadas + fecha_elaboracion + 30 días < hoy
        $facturas = $db->table('tbl_facturacion f')
            ->select('f.id_facturacion, f.comprobante, f.identificacion, f.nombre_tercero,
                      f.base_gravada, f.iva, ABS(f.retefuente_4) as ret4,
                      (f.base_gravada + f.iva - ABS(f.retefuente_4)) as liquido,
                      f.fecha_elaboracion, f.anticipo,
                      DATEDIFF(CURDATE(), f.fecha_elaboracion) as dias_vencida,
                      p.portafolio')
            ->join('tbl_portafolios p', 'p.id_portafolio = f.id_portafolio', 'left')
            ->where('f.pagado', 0)
            ->where('f.estado_pago !=', 'castigada')
            ->where('DATE_ADD(f.fecha_elaboracion, INTERVAL 30 DAY) <', date('Y-m-d'))
            ->orderBy('f.identificacion')
            ->orderBy('f.fecha_elaboracion', 'ASC')
            ->get()->getResultArray();

        if (empty($facturas)) {
            $resultado['detalle'][] = 'No hay facturas vencidas.';
            return $resultado;
        }

        // Agrupar por NIT
        $porNit = [];
        foreach ($facturas as $f) {
            $porNit[$f['identificacion']][] = $f;
        }

        foreach ($porNit as $nit => $facturasCliente) {
            $emailCliente = $this->buscarEmailPorNit($nit);
            $nombreCliente = $facturasCliente[0]['nombre_tercero'];

            if (!$emailCliente) {
                $resultado['sin_email']++;
                $resultado['detalle'][] = "NIT {$nit} ({$nombreCliente}): sin email registrado.";
                continue;
            }

            $html = $this->generarHTML($nombreCliente, $nit, $facturasCliente);
            $asunto = "Cycloid Talent - Recordatorio de cartera pendiente - NIT {$nit}";

            // === MODO TEST: solo envía a head.consultant para verificar fórmula corregida ===
            // REVERTIR antes del próximo jueves par (14/05/2026)
            // $enviado = $this->enviarEmail($emailCliente, $nombreCliente, $asunto, $html, [$this->ccDiana, $this->ccEdison]);
            $asuntoTest = "[TEST {$nit}] {$nombreCliente} — habria ido a {$emailCliente}";
            $enviado = $this->enviarEmail($this->ccEdison, 'Edison Cuervo (TEST)', $asuntoTest, $html, []);
            // === FIN MODO TEST ===

            if ($enviado) {
                $resultado['enviados']++;
                $resultado['detalle'][] = "NIT {$nit} ({$nombreCliente}): email enviado a {$emailCliente}";
            } else {
                $resultado['errores']++;
                $resultado['detalle'][] = "NIT {$nit} ({$nombreCliente}): ERROR al enviar a {$emailCliente}";
            }
        }

        return $resultado;
    }

    private function buscarEmailPorNit(int $nit): ?string
    {
        $bases = [
            ['db' => 'propiedad_horizontal', 'query' => "SELECT correo_cliente as email FROM tbl_clientes WHERE nit_cliente = {$nit} AND correo_cliente != '' LIMIT 1"],
            ['db' => 'empresas_sst',         'query' => "SELECT correo_cliente as email FROM tbl_clientes WHERE nit_cliente = {$nit} AND correo_cliente != '' LIMIT 1"],
            ['db' => 'psyrisk',              'query' => "SELECT contact_email as email FROM companies WHERE nit = '{$nit}' AND contact_email != '' LIMIT 1"],
        ];

        foreach ($bases as $b) {
            try {
                $conn = new \mysqli($this->extHost, $this->extUser, $this->extPass, $b['db'], $this->extPort);
                if ($conn->connect_error) continue;

                $r = $conn->query($b['query']);
                if ($r && $r->num_rows > 0) {
                    $email = trim($r->fetch_assoc()['email']);
                    $conn->close();
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return $email;
                    }
                }
                $conn->close();
            } catch (\Exception $e) {
                log_message('error', "NotificadorCarteraVencida: Error consultando {$b['db']} para NIT {$nit}: " . $e->getMessage());
            }
        }

        return null;
    }

    private function generarHTML(string $nombre, int $nit, array $facturas): string
    {
        $filas = '';
        $totalPendiente = 0;
        foreach ($facturas as $f) {
            $anticipo = (float)$f['anticipo'];
            $aConsignar = (float)$f['liquido'] - $anticipo;
            $totalPendiente += $aConsignar;
            $anticipoTxt = $anticipo > 0 ? "anticipo $" . number_format($anticipo, 0, ',', '.') : '—';
            $filas .= "<tr>
                <td style='padding:8px; border:1px solid #ddd;'>{$f['comprobante']}</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . date('d/m/Y', strtotime($f['fecha_elaboracion'])) . "</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:center;'>{$f['dias_vencida']} dias</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:center; color:#6c757d; font-size:12px;'>{$anticipoTxt}</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:right;'>$" . number_format($aConsignar, 0, ',', '.') . "</td>
            </tr>";
        }

        return "
        <div style='font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto;'>
            <div style='background: #212529; color: white; padding: 15px 20px; border-radius: 8px 8px 0 0;'>
                <h2 style='margin:0; font-size:18px;'>Cycloid Talent - Recordatorio de Cartera Pendiente</h2>
            </div>
            <div style='padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;'>
                <p>Estimado(a) administrador(a) de <strong>{$nombre}</strong> (NIT: {$nit}),</p>
                <p>Nos permitimos recordarle que a la fecha registramos la(s) siguiente(s) factura(s) pendiente(s) de pago:</p>

                <table style='width:100%; border-collapse:collapse; margin:15px 0; font-size:14px;'>
                    <thead>
                        <tr style='background:#f8f9fa;'>
                            <th style='padding:8px; border:1px solid #ddd; text-align:left;'>Factura</th>
                            <th style='padding:8px; border:1px solid #ddd; text-align:left;'>Fecha Elaboracion</th>
                            <th style='padding:8px; border:1px solid #ddd; text-align:center;'>Dias Vencida</th>
                            <th style='padding:8px; border:1px solid #ddd; text-align:center;'>Anticipo</th>
                            <th style='padding:8px; border:1px solid #ddd; text-align:right;'>Valor a Consignar</th>
                        </tr>
                    </thead>
                    <tbody>{$filas}</tbody>
                    <tfoot>
                        <tr style='background:#f8f9fa; font-weight:bold;'>
                            <td colspan='4' style='padding:8px; border:1px solid #ddd; text-align:right;'>Total pendiente:</td>
                            <td style='padding:8px; border:1px solid #ddd; text-align:right; color:#dc3545;'>$" . number_format($totalPendiente, 0, ',', '.') . "</td>
                        </tr>
                    </tfoot>
                </table>

                <p>Agradecemos gestionar el pago a la mayor brevedad posible. Si ya realizo el pago, por favor haga caso omiso de este mensaje y envienos el soporte para actualizar nuestros registros.</p>
                <p>Quedamos atentos a cualquier inquietud.</p>

                <p>Cordialmente,</p>
                <p><strong>Cycloid Talent SAS</strong><br>Area Comercial</p>

                <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>
                <p style='font-size:12px; color:#6c757d;'>
                    Este es un mensaje automatico generado por el sistema KPI de Cycloid Talent SAS.<br>
                    Por favor responda a este correo si tiene alguna consulta.
                </p>
            </div>
        </div>";
    }

    private function enviarEmail(string $emailDestino, string $nombreDestino, string $asunto, string $contenidoHTML, array $copias = []): bool
    {
        try {
            $apiKey = env('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                log_message('error', 'NotificadorCarteraVencida: SENDGRID_API_KEY no configurada');
                return false;
            }

            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($asunto);
            $email->addTo($emailDestino, $nombreDestino ?: $emailDestino);

            foreach ($copias as $cc) {
                if (strtolower(trim($cc)) !== strtolower(trim($emailDestino))) {
                    $email->addCc($cc);
                }
            }

            $email->addContent("text/html", $contenidoHTML);

            $sendgrid = new \SendGrid($apiKey);
            $response = $sendgrid->send($email);
            $statusCode = $response->statusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                log_message('info', "NotificadorCarteraVencida: Email enviado a {$emailDestino} (CC: " . implode(', ', $copias) . ") - {$asunto}");
                return true;
            } else {
                log_message('error', "NotificadorCarteraVencida: Error SendGrid - Status: {$statusCode} - Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'NotificadorCarteraVencida: Excepcion - ' . $e->getMessage());
            return false;
        }
    }
}
