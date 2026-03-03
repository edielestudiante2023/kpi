<?php

namespace App\Models;

use CodeIgniter\Model;

class BitacoraActividadModel extends Model
{
    protected $table      = 'bitacora_actividades';
    protected $primaryKey = 'id_bitacora';
    protected $returnType = 'array';

    protected $allowedFields = [
        'id_usuario',
        'numero_actividad',
        'descripcion',
        'id_centro_costo',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_minutos',
        'estado',
    ];

    protected $useTimestamps = false;

    /**
     * Actividades de un usuario en una fecha, con nombre de centro de costo
     */
    public function getActividadesDelDia(int $idUsuario, string $fecha): array
    {
        return $this->select('bitacora_actividades.*, centros_costo.nombre AS centro_costo_nombre')
                     ->join('centros_costo', 'centros_costo.id_centro_costo = bitacora_actividades.id_centro_costo', 'left')
                     ->where('bitacora_actividades.id_usuario', $idUsuario)
                     ->where('bitacora_actividades.fecha', $fecha)
                     ->orderBy('bitacora_actividades.numero_actividad', 'ASC')
                     ->findAll();
    }

    /**
     * Actividad actualmente en progreso del usuario (máximo 1)
     */
    public function getActividadEnProgreso(int $idUsuario): ?array
    {
        $result = $this->select('bitacora_actividades.*, centros_costo.nombre AS centro_costo_nombre')
                       ->join('centros_costo', 'centros_costo.id_centro_costo = bitacora_actividades.id_centro_costo', 'left')
                       ->where('bitacora_actividades.id_usuario', $idUsuario)
                       ->where('bitacora_actividades.estado', 'en_progreso')
                       ->first();
        return $result ?: null;
    }

    /**
     * Siguiente número secuencial de actividad para el usuario en esa fecha
     */
    public function getNextNumeroActividad(int $idUsuario, string $fecha): int
    {
        $max = $this->selectMax('numero_actividad', 'max_num')
                     ->where('id_usuario', $idUsuario)
                     ->where('fecha', $fecha)
                     ->first();
        return ($max['max_num'] ?? 0) + 1;
    }

    /**
     * Total de minutos trabajados en el día
     */
    public function getTotalMinutosDia(int $idUsuario, string $fecha): float
    {
        $result = $this->selectSum('duracion_minutos', 'total')
                       ->where('id_usuario', $idUsuario)
                       ->where('fecha', $fecha)
                       ->where('estado', 'finalizada')
                       ->first();
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Resumen diario de un usuario para un mes
     */
    public function getResumenMensual(int $idUsuario, int $anio, int $mes): array
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT
                fecha,
                SUM(CASE WHEN estado = 'finalizada' THEN duracion_minutos ELSE 0 END) AS total_minutos,
                COUNT(*) AS num_actividades,
                MIN(hora_inicio) AS primera_entrada,
                MAX(COALESCE(hora_fin, hora_inicio)) AS ultima_salida
            FROM bitacora_actividades
            WHERE id_usuario = ?
              AND YEAR(fecha) = ?
              AND MONTH(fecha) = ?
            GROUP BY fecha
            ORDER BY fecha DESC
        ", [$idUsuario, $anio, $mes])->getResultArray();
    }

    /**
     * Resumen mensual de TODOS los usuarios habilitados
     */
    public function getResumenEquipoMensual(int $anio, int $mes): array
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT
                u.id_users,
                u.nombre_completo,
                COALESCE(SUM(CASE WHEN ba.estado = 'finalizada' THEN ba.duracion_minutos ELSE 0 END), 0) AS total_minutos,
                COUNT(DISTINCT ba.fecha) AS dias_registrados,
                COUNT(ba.id_bitacora) AS num_actividades
            FROM users u
            LEFT JOIN bitacora_actividades ba ON ba.id_usuario = u.id_users
                AND YEAR(ba.fecha) = ? AND MONTH(ba.fecha) = ?
            WHERE u.bitacora_habilitada = 1
            GROUP BY u.id_users, u.nombre_completo
            ORDER BY total_minutos DESC
        ", [$anio, $mes])->getResultArray();
    }

    /**
     * Resumen diario de un usuario especifico para un mes (vista jefe)
     */
    public function getResumenMensualUsuario(int $idUsuario, int $anio, int $mes): array
    {
        return $this->getResumenMensual($idUsuario, $anio, $mes);
    }

    /**
     * Total minutos finalizados en un rango de fechas (para liquidación)
     */
    public function getTotalMinutosRango(int $idUsuario, string $desde, string $hasta): float
    {
        $db = \Config\Database::connect();
        $result = $db->query("
            SELECT COALESCE(SUM(duracion_minutos), 0) AS total
            FROM bitacora_actividades
            WHERE id_usuario = ?
              AND estado = 'finalizada'
              AND hora_inicio >= ?
              AND hora_fin <= ?
        ", [$idUsuario, $desde, $hasta])->getRowArray();
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Total de minutos por día en un rango de fechas (para tabla quincenal)
     */
    public function getResumenDiarioRango(int $idUsuario, string $desde, string $hasta): array
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT
                fecha,
                SUM(duracion_minutos) AS total_minutos
            FROM bitacora_actividades
            WHERE id_usuario = ?
              AND estado = 'finalizada'
              AND fecha >= DATE(?)
              AND fecha <= DATE(?)
            GROUP BY fecha
            ORDER BY fecha ASC
        ", [$idUsuario, $desde, $hasta])->getResultArray();
    }

    /**
     * Todas las actividades en progreso (para corte de liquidación)
     */
    public function getTodasEnProgreso(): array
    {
        return $this->where('estado', 'en_progreso')->findAll();
    }
}
