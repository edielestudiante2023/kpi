<?php

namespace App\Libraries;

use App\Models\UserModel;
use App\Models\ActividadModel;

require_once ROOTPATH . 'vendor/autoload.php';

class NotificadorActividades
{
    protected $userModel;
    protected $fromEmail = 'notificacion.cycloidtalent@cycloidtalent.com';
    protected $fromName = 'Kpi Cycloid - Actividades';

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Notifica al usuario cuando le asignan una actividad
     */
    public function notificarAsignacion(array $actividad, int $idUsuarioAsignado): bool
    {
        $usuario = $this->userModel->find($idUsuarioAsignado);
        if (!$usuario || empty($usuario['correo'])) {
            log_message('warning', 'NotificadorActividades: Usuario sin correo - ID: ' . $idUsuarioAsignado);
            return false;
        }

        $asunto = "Nueva actividad asignada: {$actividad['codigo']}";

        $urlActividad = base_url('actividades/ver/' . $actividad['id_actividad']);
        $fechaLimite = $actividad['fecha_limite']
            ? date('d/m/Y', strtotime($actividad['fecha_limite']))
            : 'Sin fecha límite';

        $prioridadColor = $this->getColorPrioridad($actividad['prioridad']);

        $contenidoHTML = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>Nueva Actividad Asignada</h1>
            </div>

            <div style='padding: 30px; background: #f8f9fa;'>
                <p style='font-size: 16px; color: #333;'>Hola <strong>{$usuario['nombre_completo']}</strong>,</p>
                <p style='font-size: 16px; color: #333;'>Se te ha asignado una nueva actividad:</p>

                <div style='background: white; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid {$prioridadColor};'>
                    <p style='margin: 0 0 10px 0;'>
                        <span style='color: #6c757d; font-size: 12px;'>{$actividad['codigo']}</span>
                    </p>
                    <h2 style='margin: 0 0 15px 0; color: #333; font-size: 20px;'>{$actividad['titulo']}</h2>

                    <table style='width: 100%; font-size: 14px;'>
                        <tr>
                            <td style='padding: 5px 0; color: #6c757d;'>Prioridad:</td>
                            <td style='padding: 5px 0;'>
                                <span style='background: {$prioridadColor}; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px;'>
                                    " . ucfirst($actividad['prioridad']) . "
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 5px 0; color: #6c757d;'>Fecha límite:</td>
                            <td style='padding: 5px 0; font-weight: bold;'>{$fechaLimite}</td>
                        </tr>
                    </table>

                    " . ($actividad['descripcion'] ? "<p style='margin: 15px 0 0 0; color: #555; font-size: 14px; border-top: 1px solid #eee; padding-top: 15px;'>{$actividad['descripcion']}</p>" : "") . "
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$urlActividad}' style='display: inline-block; padding: 14px 28px; background: #0d6efd; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                        Ver Actividad
                    </a>
                </div>
            </div>

            <div style='padding: 20px; background: #e9ecef; text-align: center; font-size: 12px; color: #6c757d;'>
                <p style='margin: 0;'>Este es un mensaje automático del sistema Kpi Cycloid.</p>
                <p style='margin: 5px 0 0 0;'>Por favor no responda a este correo.</p>
            </div>
        </div>
        ";

        return $this->enviarEmail($usuario['correo'], $usuario['nombre_completo'], $asunto, $contenidoHTML);
    }

    /**
     * Notifica cuando una actividad cambia de estado
     */
    public function notificarCambioEstado(array $actividad, string $estadoAnterior, string $estadoNuevo, int $idUsuarioQueModifico): bool
    {
        $destinatarios = [];

        // Notificar al creador (si no es quien modificó)
        if ($actividad['id_usuario_creador'] != $idUsuarioQueModifico) {
            $creador = $this->userModel->find($actividad['id_usuario_creador']);
            if ($creador && !empty($creador['correo'])) {
                $destinatarios[] = $creador;
            }
        }

        // Notificar al asignado (si existe y no es quien modificó)
        if (!empty($actividad['id_usuario_asignado']) && $actividad['id_usuario_asignado'] != $idUsuarioQueModifico) {
            $asignado = $this->userModel->find($actividad['id_usuario_asignado']);
            if ($asignado && !empty($asignado['correo'])) {
                $destinatarios[] = $asignado;
            }
        }

        if (empty($destinatarios)) {
            return true; // No hay a quién notificar
        }

        $usuarioQueModifico = $this->userModel->find($idUsuarioQueModifico);
        $nombreQuienModifico = $usuarioQueModifico['nombre_completo'] ?? 'Un usuario';

        $asunto = "Actividad {$actividad['codigo']} cambió a: " . ucfirst(str_replace('_', ' ', $estadoNuevo));

        $urlActividad = base_url('actividades/ver/' . $actividad['id_actividad']);
        $colorEstado = $this->getColorEstado($estadoNuevo);

        $exito = true;
        foreach ($destinatarios as $usuario) {
            $contenidoHTML = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: {$colorEstado}; padding: 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>Cambio de Estado</h1>
                </div>

                <div style='padding: 30px; background: #f8f9fa;'>
                    <p style='font-size: 16px; color: #333;'>Hola <strong>{$usuario['nombre_completo']}</strong>,</p>
                    <p style='font-size: 16px; color: #333;'><strong>{$nombreQuienModifico}</strong> ha actualizado el estado de una actividad:</p>

                    <div style='background: white; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                        <p style='margin: 0 0 10px 0;'>
                            <span style='color: #6c757d; font-size: 12px;'>{$actividad['codigo']}</span>
                        </p>
                        <h2 style='margin: 0 0 20px 0; color: #333; font-size: 20px;'>{$actividad['titulo']}</h2>

                        <div style='text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;'>
                            <span style='background: #6c757d; color: white; padding: 5px 15px; border-radius: 15px; font-size: 14px;'>
                                " . ucfirst(str_replace('_', ' ', $estadoAnterior)) . "
                            </span>
                            <span style='margin: 0 15px; font-size: 20px;'>→</span>
                            <span style='background: {$colorEstado}; color: white; padding: 5px 15px; border-radius: 15px; font-size: 14px;'>
                                " . ucfirst(str_replace('_', ' ', $estadoNuevo)) . "
                            </span>
                        </div>
                    </div>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$urlActividad}' style='display: inline-block; padding: 14px 28px; background: #0d6efd; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                            Ver Actividad
                        </a>
                    </div>
                </div>

                <div style='padding: 20px; background: #e9ecef; text-align: center; font-size: 12px; color: #6c757d;'>
                    <p style='margin: 0;'>Este es un mensaje automático del sistema Kpi Cycloid.</p>
                </div>
            </div>
            ";

            if (!$this->enviarEmail($usuario['correo'], $usuario['nombre_completo'], $asunto, $contenidoHTML)) {
                $exito = false;
            }
        }

        return $exito;
    }

    /**
     * Notifica cuando se agrega un comentario
     */
    public function notificarComentario(array $actividad, array $comentario, int $idUsuarioQueComento): bool
    {
        $destinatarios = [];

        // Notificar al creador (si no es quien comentó)
        if ($actividad['id_usuario_creador'] != $idUsuarioQueComento) {
            $creador = $this->userModel->find($actividad['id_usuario_creador']);
            if ($creador && !empty($creador['correo'])) {
                $destinatarios[] = $creador;
            }
        }

        // Notificar al asignado (si existe y no es quien comentó)
        if (!empty($actividad['id_usuario_asignado']) && $actividad['id_usuario_asignado'] != $idUsuarioQueComento) {
            $asignado = $this->userModel->find($actividad['id_usuario_asignado']);
            if ($asignado && !empty($asignado['correo'])) {
                $destinatarios[] = $asignado;
            }
        }

        if (empty($destinatarios)) {
            return true;
        }

        $usuarioQueComento = $this->userModel->find($idUsuarioQueComento);
        $nombreQuienComento = $usuarioQueComento['nombre_completo'] ?? 'Un usuario';

        $asunto = "Nuevo comentario en {$actividad['codigo']}";
        $urlActividad = base_url('actividades/ver/' . $actividad['id_actividad']);

        $exito = true;
        foreach ($destinatarios as $usuario) {
            $contenidoHTML = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: #17a2b8; padding: 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>Nuevo Comentario</h1>
                </div>

                <div style='padding: 30px; background: #f8f9fa;'>
                    <p style='font-size: 16px; color: #333;'>Hola <strong>{$usuario['nombre_completo']}</strong>,</p>
                    <p style='font-size: 16px; color: #333;'><strong>{$nombreQuienComento}</strong> ha comentado en una actividad:</p>

                    <div style='background: white; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                        <p style='margin: 0 0 10px 0;'>
                            <span style='color: #6c757d; font-size: 12px;'>{$actividad['codigo']}</span>
                        </p>
                        <h3 style='margin: 0 0 15px 0; color: #333;'>{$actividad['titulo']}</h3>

                        <div style='background: #f8f9fa; border-left: 4px solid #17a2b8; padding: 15px; margin-top: 15px;'>
                            <p style='margin: 0; color: #555; font-style: italic;'>\"{$comentario['comentario']}\"</p>
                            <p style='margin: 10px 0 0 0; font-size: 12px; color: #6c757d;'>— {$nombreQuienComento}</p>
                        </div>
                    </div>

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$urlActividad}' style='display: inline-block; padding: 14px 28px; background: #0d6efd; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                            Ver Actividad
                        </a>
                    </div>
                </div>

                <div style='padding: 20px; background: #e9ecef; text-align: center; font-size: 12px; color: #6c757d;'>
                    <p style='margin: 0;'>Este es un mensaje automático del sistema Kpi Cycloid.</p>
                </div>
            </div>
            ";

            if (!$this->enviarEmail($usuario['correo'], $usuario['nombre_completo'], $asunto, $contenidoHTML)) {
                $exito = false;
            }
        }

        return $exito;
    }

    /**
     * Notifica actividades próximas a vencer o vencidas
     */
    public function notificarVencimiento(array $actividad, bool $esVencida = false): bool
    {
        if (empty($actividad['id_usuario_asignado'])) {
            return true;
        }

        $usuario = $this->userModel->find($actividad['id_usuario_asignado']);
        if (!$usuario || empty($usuario['correo'])) {
            return false;
        }

        $urlActividad = base_url('actividades/ver/' . $actividad['id_actividad']);
        $fechaLimite = date('d/m/Y', strtotime($actividad['fecha_limite']));

        if ($esVencida) {
            $asunto = "URGENTE: Actividad vencida - {$actividad['codigo']}";
            $colorHeader = '#dc3545';
            $titulo = 'Actividad Vencida';
            $mensaje = "La siguiente actividad <strong>ha vencido</strong> y requiere tu atención inmediata:";
        } else {
            $asunto = "Recordatorio: Actividad próxima a vencer - {$actividad['codigo']}";
            $colorHeader = '#ffc107';
            $titulo = 'Actividad Próxima a Vencer';
            $mensaje = "La siguiente actividad <strong>vence mañana</strong>:";
        }

        $contenidoHTML = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: {$colorHeader}; padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>{$titulo}</h1>
            </div>

            <div style='padding: 30px; background: #f8f9fa;'>
                <p style='font-size: 16px; color: #333;'>Hola <strong>{$usuario['nombre_completo']}</strong>,</p>
                <p style='font-size: 16px; color: #333;'>{$mensaje}</p>

                <div style='background: white; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid {$colorHeader};'>
                    <p style='margin: 0 0 10px 0;'>
                        <span style='color: #6c757d; font-size: 12px;'>{$actividad['codigo']}</span>
                    </p>
                    <h2 style='margin: 0 0 15px 0; color: #333; font-size: 20px;'>{$actividad['titulo']}</h2>

                    <p style='margin: 0; font-size: 16px;'>
                        <strong>Fecha límite:</strong>
                        <span style='color: {$colorHeader}; font-weight: bold;'>{$fechaLimite}</span>
                    </p>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$urlActividad}' style='display: inline-block; padding: 14px 28px; background: #0d6efd; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                        Ver Actividad
                    </a>
                </div>
            </div>

            <div style='padding: 20px; background: #e9ecef; text-align: center; font-size: 12px; color: #6c757d;'>
                <p style='margin: 0;'>Este es un mensaje automático del sistema Kpi Cycloid.</p>
            </div>
        </div>
        ";

        return $this->enviarEmail($usuario['correo'], $usuario['nombre_completo'], $asunto, $contenidoHTML);
    }

    /**
     * Envía el email usando SendGrid
     */
    protected function enviarEmail(string $emailDestino, string $nombreDestino, string $asunto, string $contenidoHTML): bool
    {
        try {
            // Usar env() de CodeIgniter en lugar de getenv()
            $apiKey = env('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                log_message('error', 'NotificadorActividades: SENDGRID_API_KEY no configurada');
                return false;
            }

            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($asunto);
            $email->addTo($emailDestino, $nombreDestino);
            $email->addContent("text/html", $contenidoHTML);

            $sendgrid = new \SendGrid($apiKey);
            $response = $sendgrid->send($email);
            $statusCode = $response->statusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                log_message('info', "NotificadorActividades: Email enviado a {$emailDestino} - Asunto: {$asunto}");
                return true;
            } else {
                log_message('error', "NotificadorActividades: Error SendGrid - Status: {$statusCode} - Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'NotificadorActividades: Excepción - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia resumen diario de actividades a cada usuario
     */
    public function enviarResumenDiario(): array
    {
        $actividadModel = new ActividadModel();
        $usuarios = $this->userModel->where('activo', 1)->findAll();

        $resultados = [
            'enviados' => 0,
            'errores' => 0,
            'sin_actividades' => 0
        ];

        foreach ($usuarios as $usuario) {
            if (empty($usuario['correo'])) {
                continue;
            }

            // Obtener actividades asignadas al usuario (no completadas/canceladas)
            $actividadesAsignadas = $actividadModel->getActividadesCompletas([
                'id_asignado' => $usuario['id_users']
            ]);

            // Filtrar solo activas
            $actividadesActivas = array_filter($actividadesAsignadas, function($act) {
                return !in_array($act['estado'], ['completada', 'cancelada']);
            });

            if (empty($actividadesActivas)) {
                $resultados['sin_actividades']++;
                continue;
            }

            // Clasificar actividades
            $vencidas = [];
            $hoy = [];
            $proximasVencer = [];
            $enProgreso = [];
            $pendientes = [];

            foreach ($actividadesActivas as $act) {
                $diasRestantes = $act['dias_restantes'];

                // Clasificar por urgencia
                if ($diasRestantes !== null && $diasRestantes !== '' && (int)$diasRestantes < 0) {
                    $vencidas[] = $act;
                } elseif ($diasRestantes !== null && $diasRestantes !== '' && (int)$diasRestantes == 0) {
                    $hoy[] = $act;
                } elseif ($diasRestantes !== null && $diasRestantes !== '' && (int)$diasRestantes <= 3) {
                    $proximasVencer[] = $act;
                } elseif ($act['estado'] === 'en_progreso' || $act['estado'] === 'en_revision') {
                    $enProgreso[] = $act;
                } else {
                    $pendientes[] = $act;
                }
            }

            // Generar HTML del resumen
            $contenidoHTML = $this->generarHTMLResumenDiario(
                $usuario,
                $vencidas,
                $hoy,
                $proximasVencer,
                $enProgreso,
                $pendientes
            );

            $asunto = "Resumen diario de actividades - " . date('d/m/Y');

            if ($this->enviarEmail($usuario['correo'], $usuario['nombre_completo'], $asunto, $contenidoHTML)) {
                $resultados['enviados']++;
            } else {
                $resultados['errores']++;
            }
        }

        return $resultados;
    }

    /**
     * Genera el HTML del resumen diario
     */
    protected function generarHTMLResumenDiario(
        array $usuario,
        array $vencidas,
        array $hoy,
        array $proximasVencer,
        array $enProgreso,
        array $pendientes
    ): string {
        $urlTablero = base_url('actividades/tablero');
        $totalActivas = count($vencidas) + count($hoy) + count($proximasVencer) + count($enProgreso) + count($pendientes);

        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>Resumen Diario</h1>
                <p style='color: rgba(255,255,255,0.8); margin: 5px 0 0 0;'>" . date('d/m/Y') . "</p>
            </div>

            <div style='padding: 30px; background: #f8f9fa;'>
                <p style='font-size: 16px; color: #333;'>Hola <strong>{$usuario['nombre_completo']}</strong>,</p>
                <p style='font-size: 16px; color: #333;'>Tienes <strong>{$totalActivas}</strong> actividades pendientes:</p>

                <div style='display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap;'>
                    <div style='flex: 1; min-width: 80px; background: #dc3545; color: white; padding: 15px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 24px; font-weight: bold;'>" . count($vencidas) . "</div>
                        <div style='font-size: 11px;'>VENCIDAS</div>
                    </div>
                    <div style='flex: 1; min-width: 80px; background: #fd7e14; color: white; padding: 15px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 24px; font-weight: bold;'>" . count($hoy) . "</div>
                        <div style='font-size: 11px;'>VENCEN HOY</div>
                    </div>
                    <div style='flex: 1; min-width: 80px; background: #ffc107; color: #333; padding: 15px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 24px; font-weight: bold;'>" . count($proximasVencer) . "</div>
                        <div style='font-size: 11px;'>PROXIMAS</div>
                    </div>
                    <div style='flex: 1; min-width: 80px; background: #0d6efd; color: white; padding: 15px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 24px; font-weight: bold;'>" . count($enProgreso) . "</div>
                        <div style='font-size: 11px;'>EN PROGRESO</div>
                    </div>
                    <div style='flex: 1; min-width: 80px; background: #6c757d; color: white; padding: 15px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 24px; font-weight: bold;'>" . count($pendientes) . "</div>
                        <div style='font-size: 11px;'>PENDIENTES</div>
                    </div>
                </div>";

        // Sección de vencidas (urgente)
        if (!empty($vencidas)) {
            $html .= $this->generarSeccionActividades('VENCIDAS - Requieren atencion inmediata', $vencidas, '#dc3545');
        }

        // Sección de hoy
        if (!empty($hoy)) {
            $html .= $this->generarSeccionActividades('Vencen HOY', $hoy, '#fd7e14');
        }

        // Sección próximas a vencer
        if (!empty($proximasVencer)) {
            $html .= $this->generarSeccionActividades('Proximas a vencer (3 dias)', $proximasVencer, '#ffc107');
        }

        // Sección en progreso (máximo 5)
        if (!empty($enProgreso)) {
            $html .= $this->generarSeccionActividades('En progreso', array_slice($enProgreso, 0, 5), '#0d6efd');
        }

        // Sección pendientes (máximo 5)
        if (!empty($pendientes)) {
            $html .= $this->generarSeccionActividades('Pendientes', array_slice($pendientes, 0, 5), '#6c757d');
        }

        $html .= "
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$urlTablero}' style='display: inline-block; padding: 14px 28px; background: #0d6efd; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                        Ver Tablero Completo
                    </a>
                </div>
            </div>

            <div style='padding: 20px; background: #e9ecef; text-align: center; font-size: 12px; color: #6c757d;'>
                <p style='margin: 0;'>Este es un mensaje automatico del sistema Kpi Cycloid.</p>
                <p style='margin: 5px 0 0 0;'>Enviado cada dia a las 7:00 AM.</p>
            </div>
        </div>
        ";

        return $html;
    }

    /**
     * Genera una seccion de actividades para el resumen
     */
    protected function generarSeccionActividades(string $titulo, array $actividades, string $color): string
    {
        $html = "
        <div style='margin: 20px 0;'>
            <h3 style='color: {$color}; font-size: 14px; margin: 0 0 10px 0; border-bottom: 2px solid {$color}; padding-bottom: 5px;'>
                {$titulo}
            </h3>";

        foreach ($actividades as $act) {
            $urlActividad = base_url('actividades/ver/' . $act['id_actividad']);
            $fechaLimite = $act['fecha_limite'] ? date('d/m', strtotime($act['fecha_limite'])) : '-';

            $html .= "
            <div style='background: white; padding: 10px; margin-bottom: 8px; border-radius: 4px; border-left: 3px solid {$color};'>
                <a href='{$urlActividad}' style='color: #333; text-decoration: none; font-weight: 500;'>{$act['titulo']}</a>
                <div style='font-size: 11px; color: #6c757d; margin-top: 3px;'>
                    {$act['codigo']} | Vence: {$fechaLimite}
                </div>
            </div>";
        }

        $html .= "</div>";
        return $html;
    }

    /**
     * Obtiene el color según la prioridad
     */
    protected function getColorPrioridad(string $prioridad): string
    {
        return match ($prioridad) {
            'urgente' => '#dc3545',
            'alta' => '#fd7e14',
            'media' => '#ffc107',
            'baja' => '#198754',
            default => '#6c757d'
        };
    }

    /**
     * Obtiene el color según el estado
     */
    protected function getColorEstado(string $estado): string
    {
        return match ($estado) {
            'pendiente' => '#6c757d',
            'en_progreso' => '#0d6efd',
            'en_revision' => '#6f42c1',
            'completada' => '#198754',
            'cancelada' => '#dc3545',
            default => '#6c757d'
        };
    }
}
