<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionUsuarioModel extends Model
{
    protected $table            = 'sesiones_usuario';
    protected $primaryKey       = 'id_sesion';
    protected $allowedFields    = [
        'id_usuario',
        'token_sesion',
        'fecha_inicio',
        'fecha_ultimo_latido',
        'fecha_fin',
        'ip_address',
        'user_agent',
        'activa'
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    /**
     * Inicia una nueva sesion para el usuario
     */
    public function iniciarSesion($idUsuario, $ip = null, $userAgent = null): array
    {
        // Cerrar sesiones anteriores activas del usuario
        $this->cerrarSesionesUsuario($idUsuario);

        $token = bin2hex(random_bytes(32));

        $data = [
            'id_usuario'         => $idUsuario,
            'token_sesion'       => $token,
            'fecha_inicio'       => date('Y-m-d H:i:s'),
            'fecha_ultimo_latido'=> date('Y-m-d H:i:s'),
            'ip_address'         => $ip,
            'user_agent'         => substr($userAgent ?? '', 0, 512),
            'activa'             => 1
        ];

        $this->insert($data);

        return [
            'id_sesion' => $this->getInsertID(),
            'token'     => $token
        ];
    }

    /**
     * Actualiza el latido de una sesion activa
     */
    public function actualizarLatido($token): bool
    {
        return $this->where('token_sesion', $token)
                    ->where('activa', 1)
                    ->set('fecha_ultimo_latido', date('Y-m-d H:i:s'))
                    ->update();
    }

    /**
     * Cierra una sesion especifica
     */
    public function cerrarSesion($token): bool
    {
        $sesion = $this->where('token_sesion', $token)
                       ->where('activa', 1)
                       ->first();

        if (!$sesion) {
            return false;
        }

        return $this->update($sesion['id_sesion'], [
            'activa'    => 0,
            'fecha_fin' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Cierra todas las sesiones activas de un usuario
     */
    public function cerrarSesionesUsuario($idUsuario): bool
    {
        return $this->where('id_usuario', $idUsuario)
                    ->where('activa', 1)
                    ->set([
                        'activa'    => 0,
                        'fecha_fin' => date('Y-m-d H:i:s')
                    ])
                    ->update();
    }

    /**
     * Cierra sesiones inactivas (sin latido en X minutos)
     */
    public function cerrarSesionesInactivas($minutosInactividad = 10): int
    {
        $limite = date('Y-m-d H:i:s', strtotime("-{$minutosInactividad} minutes"));

        $sesiones = $this->where('activa', 1)
                         ->where('fecha_ultimo_latido <', $limite)
                         ->findAll();

        foreach ($sesiones as $sesion) {
            $this->update($sesion['id_sesion'], [
                'activa'    => 0,
                'fecha_fin' => $sesion['fecha_ultimo_latido']
            ]);
        }

        return count($sesiones);
    }

    /**
     * Obtiene la sesion activa de un usuario
     */
    public function getSesionActiva($idUsuario): ?array
    {
        return $this->where('id_usuario', $idUsuario)
                    ->where('activa', 1)
                    ->first();
    }

    /**
     * Obtiene todas las sesiones con informacion de usuario
     */
    public function getSesionesConUsuario($filtros = []): array
    {
        $builder = $this->db->table('vw_sesiones_usuario');

        if (!empty($filtros['id_usuario'])) {
            $builder->where('id_usuario', $filtros['id_usuario']);
        }
        if (!empty($filtros['fecha_desde'])) {
            $builder->where('fecha_inicio >=', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $builder->where('fecha_inicio <=', $filtros['fecha_hasta'] . ' 23:59:59');
        }
        if (isset($filtros['activa'])) {
            $builder->where('activa', $filtros['activa']);
        }

        return $builder->orderBy('fecha_inicio', 'DESC')->get()->getResultArray();
    }

    /**
     * Obtiene resumen de uso por usuario
     */
    public function getResumenPorUsuario(): array
    {
        return $this->db->table('vw_resumen_uso_usuario')
                        ->orderBy('tiempo_total_segundos', 'DESC')
                        ->get()
                        ->getResultArray();
    }

    /**
     * Obtiene estadisticas de uso en un rango de fechas
     */
    public function getEstadisticasUso($fechaDesde = null, $fechaHasta = null): array
    {
        $fechaDesde = $fechaDesde ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $fechaHasta ?? date('Y-m-d');

        // Total de sesiones
        $totalSesiones = $this->where('fecha_inicio >=', $fechaDesde)
                              ->where('fecha_inicio <=', $fechaHasta . ' 23:59:59')
                              ->countAllResults();

        // Usuarios unicos
        $usuariosUnicos = $this->db->table('sesiones_usuario')
                                   ->select('COUNT(DISTINCT id_usuario) as total')
                                   ->where('fecha_inicio >=', $fechaDesde)
                                   ->where('fecha_inicio <=', $fechaHasta . ' 23:59:59')
                                   ->get()
                                   ->getRow()
                                   ->total;

        // Tiempo total en segundos
        $tiempoTotal = $this->db->table('sesiones_usuario')
                                ->select('SUM(
                                    CASE
                                        WHEN fecha_fin IS NOT NULL THEN TIMESTAMPDIFF(SECOND, fecha_inicio, fecha_fin)
                                        ELSE TIMESTAMPDIFF(SECOND, fecha_inicio, fecha_ultimo_latido)
                                    END
                                ) as total')
                                ->where('fecha_inicio >=', $fechaDesde)
                                ->where('fecha_inicio <=', $fechaHasta . ' 23:59:59')
                                ->get()
                                ->getRow()
                                ->total ?? 0;

        // Sesiones activas actualmente
        $sesionesActivas = $this->where('activa', 1)->countAllResults();

        // Promedio de duracion por sesion
        $promedioDuracion = $totalSesiones > 0 ? round($tiempoTotal / $totalSesiones) : 0;

        return [
            'fecha_desde'       => $fechaDesde,
            'fecha_hasta'       => $fechaHasta,
            'total_sesiones'    => $totalSesiones,
            'usuarios_unicos'   => $usuariosUnicos,
            'tiempo_total'      => $tiempoTotal,
            'sesiones_activas'  => $sesionesActivas,
            'promedio_duracion' => $promedioDuracion
        ];
    }

    /**
     * Obtiene uso por dia para graficos
     */
    public function getUsoPorDia($fechaDesde = null, $fechaHasta = null): array
    {
        $fechaDesde = $fechaDesde ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $fechaHasta ?? date('Y-m-d');

        return $this->db->table('sesiones_usuario')
                        ->select('DATE(fecha_inicio) as fecha, COUNT(*) as sesiones, COUNT(DISTINCT id_usuario) as usuarios')
                        ->where('fecha_inicio >=', $fechaDesde)
                        ->where('fecha_inicio <=', $fechaHasta . ' 23:59:59')
                        ->groupBy('DATE(fecha_inicio)')
                        ->orderBy('fecha', 'ASC')
                        ->get()
                        ->getResultArray();
    }
}
