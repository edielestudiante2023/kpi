<?php

namespace App\Libraries;

/**
 * NotificadorCartera
 *
 * Envía emails cada lunes a clientes con facturas en estado 'brecha'
 * (diferencia >= $2.000 entre líquido esperado y valor pagado).
 *
 * Busca el email del cliente por NIT en 3 bases externas:
 *   1. propiedad_horizontal.tbl_clientes (nit_cliente → correo_cliente)
 *   2. empresas_sst.tbl_clientes (nit_cliente → correo_cliente)
 *   3. psyrisk.companies (nit → contact_email)
 */
class NotificadorCartera
{
    private string $fromEmail = 'notificacion.cycloidtalent@cycloidtalent.com';
    private string $fromName  = 'Kpi Cycloid - Cartera';
    private string $ccDiana   = 'diana.cuestas@cycloidtalent.com';

    // Credenciales bases externas (desde .env)
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
     * Ejecutar notificaciones de brecha.
     * Solo envía si hoy es lunes (o si se fuerza con $forzar = true).
     */
    public function ejecutar(bool $forzar = false): array
    {
        $resultado = ['enviados' => 0, 'sin_email' => 0, 'errores' => 0, 'detalle' => []];

        // Solo lunes, a menos que se fuerce
        if (!$forzar && date('N') !== '1') {
            $resultado['detalle'][] = 'Hoy no es lunes. No se envían notificaciones.';
            return $resultado;
        }

        $db = \Config\Database::connect();

        // Obtener facturas con brecha
        $facturas = $db->table('tbl_facturacion f')
            ->select('f.id_facturacion, f.comprobante, f.identificacion, f.nombre_tercero,
                      f.base_gravada, f.iva, ABS(f.retefuente_4) as ret4,
                      (f.base_gravada + f.iva - ABS(f.retefuente_4)) as liquido,
                      f.valor_pagado, f.fecha_pago, f.fecha_elaboracion,
                      ROUND((f.base_gravada + f.iva - ABS(f.retefuente_4)) - f.valor_pagado, 2) as diferencia,
                      p.portafolio')
            ->join('tbl_portafolios p', 'p.id_portafolio = f.id_portafolio', 'left')
            ->where('f.estado_pago', 'brecha')
            ->get()->getResultArray();

        if (empty($facturas)) {
            $resultado['detalle'][] = 'No hay facturas con brecha.';
            return $resultado;
        }

        // Agrupar por NIT (un email por cliente con todas sus facturas en brecha)
        $porNit = [];
        foreach ($facturas as $f) {
            $nit = $f['identificacion'];
            $porNit[$nit][] = $f;
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
            $asunto = "Cycloid Talent - Diferencia en pago de factura(s) - NIT {$nit}";

            $enviado = $this->enviarEmail($emailCliente, $nombreCliente, $asunto, $html, [$this->ccDiana]);

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

    /**
     * Buscar email del cliente por NIT en las 3 bases externas.
     * Prioridad: propiedad_horizontal → empresas_sst → psyrisk
     */
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
                log_message('error', "NotificadorCartera: Error consultando {$b['db']} para NIT {$nit}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Generar HTML del email
     */
    private function generarHTML(string $nombre, int $nit, array $facturas): string
    {
        $filas = '';
        $totalDiferencia = 0;
        foreach ($facturas as $f) {
            $totalDiferencia += (float)$f['diferencia'];
            $filas .= "<tr>
                <td style='padding:8px; border:1px solid #ddd;'>{$f['comprobante']}</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . date('d/m/Y', strtotime($f['fecha_elaboracion'])) . "</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:right;'>$" . number_format((float)$f['liquido'], 0, ',', '.') . "</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:right;'>$" . number_format((float)$f['valor_pagado'], 0, ',', '.') . "</td>
                <td style='padding:8px; border:1px solid #ddd; text-align:right; color:#dc3545; font-weight:bold;'>$" . number_format((float)$f['diferencia'], 0, ',', '.') . "</td>
            </tr>";
        }

        return "
        <div style='font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto;'>
            <div style='background: #212529; color: white; padding: 15px 20px; border-radius: 8px 8px 0 0;'>
                <h2 style='margin:0; font-size:18px;'>Cycloid Talent - Notificacion de Cartera</h2>
            </div>
            <div style='padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;'>
                <p>Estimado(a) administrador(a) de <strong>{$nombre}</strong> (NIT: {$nit}),</p>
                <p>Le informamos que hemos identificado una diferencia entre el valor liquidado y el valor recibido en la(s) siguiente(s) factura(s):</p>

                <table style='width:100%; border-collapse:collapse; margin:15px 0; font-size:14px;'>
                    <thead>
                        <tr style='background:#f8f9fa;'>
                            <th style='padding:8px; border:1px solid #ddd; text-align:left;'>Factura</th>
                            <th style='padding:8px; border:1px solid #ddd; text-align:left;'>Fecha</th>
                            <th style='padding:8px; border:1px solid #ddd; text-align:right;'>Valor Liquidado</th>
                            <th style='padding:8px; border:1px solid #ddd; text-align:right;'>Valor Recibido</th>
                            <th style='padding:8px; border:1px solid #ddd; text-align:right;'>Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>{$filas}</tbody>
                    <tfoot>
                        <tr style='background:#f8f9fa; font-weight:bold;'>
                            <td colspan='4' style='padding:8px; border:1px solid #ddd; text-align:right;'>Total diferencia:</td>
                            <td style='padding:8px; border:1px solid #ddd; text-align:right; color:#dc3545;'>$" . number_format($totalDiferencia, 0, ',', '.') . "</td>
                        </tr>
                    </tfoot>
                </table>

                <p>Agradecemos nos indique el origen de esta diferencia para proceder con la conciliacion correspondiente.</p>
                <p>Quedamos atentos a su respuesta.</p>

                <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>
                <p style='font-size:12px; color:#6c757d;'>
                    Este es un mensaje automatico generado por el sistema KPI de Cycloid Talent SAS.<br>
                    Por favor responda a este correo si tiene alguna consulta.
                </p>
            </div>
        </div>";
    }

    /**
     * Enviar email vía SendGrid
     */
    private function enviarEmail(string $emailDestino, string $nombreDestino, string $asunto, string $contenidoHTML, array $copias = []): bool
    {
        try {
            $apiKey = env('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                log_message('error', 'NotificadorCartera: SENDGRID_API_KEY no configurada');
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
                log_message('info', "NotificadorCartera: Email enviado a {$emailDestino} (CC: " . implode(', ', $copias) . ") - {$asunto}");
                return true;
            } else {
                log_message('error', "NotificadorCartera: Error SendGrid - Status: {$statusCode} - Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'NotificadorCartera: Excepcion - ' . $e->getMessage());
            return false;
        }
    }
}
