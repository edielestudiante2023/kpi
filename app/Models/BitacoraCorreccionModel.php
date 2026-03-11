<?php

namespace App\Models;

use CodeIgniter\Model;

class BitacoraCorreccionModel extends Model
{
    protected $table         = 'bitacora_correcciones';
    protected $primaryKey    = 'id_correccion';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_bitacora', 'id_usuario', 'campo',
        'valor_anterior', 'valor_nuevo', 'motivo',
        'token', 'token_expira', 'estado',
        'aprobado_por', 'fecha_resolucion',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Busca corrección pendiente por token (no expirado)
     */
    public function getByToken(string $token): ?array
    {
        $result = $this->where('token', $token)
                       ->where('estado', 'pendiente')
                       ->where('token_expira >=', date('Y-m-d H:i:s'))
                       ->first();
        return $result ?: null;
    }

    /**
     * Verifica si ya existe una corrección pendiente para la actividad
     */
    public function tienePendiente(int $idBitacora): bool
    {
        return $this->where('id_bitacora', $idBitacora)
                    ->where('estado', 'pendiente')
                    ->where('token_expira >=', date('Y-m-d H:i:s'))
                    ->countAllResults() > 0;
    }

    /**
     * Correcciones de un usuario con datos de la actividad
     */
    public function getCorreccionesUsuario(int $idUsuario): array
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT c.*, ba.descripcion, ba.fecha, ba.hora_inicio, ba.hora_fin, ba.duracion_minutos
            FROM bitacora_correcciones c
            JOIN bitacora_actividades ba ON ba.id_bitacora = c.id_bitacora
            WHERE c.id_usuario = ?
            ORDER BY c.created_at DESC
        ", [$idUsuario])->getResultArray();
    }

    /**
     * Detalle completo de una corrección con datos de actividad y usuario
     */
    public function getDetalleConActividad(int $idCorreccion): ?array
    {
        $db = \Config\Database::connect();
        $result = $db->query("
            SELECT c.*,
                   ba.descripcion, ba.fecha, ba.hora_inicio, ba.hora_fin,
                   ba.duracion_minutos, ba.id_centro_costo,
                   cc.nombre AS centro_costo_nombre,
                   u.nombre_completo, u.correo, u.cargo, u.jornada
            FROM bitacora_correcciones c
            JOIN bitacora_actividades ba ON ba.id_bitacora = c.id_bitacora
            JOIN users u ON u.id_users = c.id_usuario
            LEFT JOIN centros_costo cc ON cc.id_centro_costo = ba.id_centro_costo
            WHERE c.id_correccion = ?
        ", [$idCorreccion])->getRowArray();
        return $result ?: null;
    }
}
