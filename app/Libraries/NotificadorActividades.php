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

        $exito = $this->enviarEmail($usuario['correo'], $usuario['nombre_completo'], $asunto, $contenidoHTML);

        // Si está vencida, también notificar al jefe
        if ($esVencida && !empty($usuario['id_jefe'])) {
            $jefe = $this->userModel->find($usuario['id_jefe']);
            if ($jefe && !empty($jefe['correo'])) {
                $asuntoJefe = "Actividad vencida de {$usuario['nombre_completo']} - {$actividad['codigo']}";
                $this->enviarEmail($jefe['correo'], $jefe['nombre_completo'], $asuntoJefe, $contenidoHTML);
            }
        }

        return $exito;
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
