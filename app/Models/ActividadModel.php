<?php

namespace App\Models;

use CodeIgniter\Model;

class ActividadModel extends Model
{
    protected $table            = 'actividades';
    protected $primaryKey       = 'id_actividad';
    protected $allowedFields    = [
        'codigo',
        'titulo',
        'descripcion',
        'id_categoria',
        'id_usuario_creador',
        'id_usuario_asignado',
        'id_area',
        'prioridad',
        'estado',
        'fecha_limite',
        'fecha_creacion',
        'fecha_actualizacion',
        'fecha_inicio',
        'fecha_cierre',
        'porcentaje_avance',
        'observaciones',
        'notificado_vencimiento',
        'requiere_revision'
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    /**
     * Genera el siguiente código de actividad
     * Formato: ACT-YYYYMMDD-0001
     */
    public function generarCodigo(): string
    {
        $fecha = date('Ymd');
        $prefijo = "ACT-{$fecha}-";

        $ultima = $this->like('codigo', $prefijo, 'after')
                       ->orderBy('codigo', 'DESC')
                       ->first();

        if ($ultima) {
            $numero = (int) substr($ultima['codigo'], -4) + 1;
        } else {
            $numero = 1;
        }

        return $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene actividades con información relacionada
     */
    public function getActividadesCompletas($filtros = [])
    {
        $builder = $this->db->table('vw_tablero_actividades');

        if (!empty($filtros['id_actividad'])) {
            $builder->where('id_actividad', $filtros['id_actividad']);
        }
        if (!empty($filtros['estado'])) {
            $builder->where('estado', $filtros['estado']);
        }
        if (!empty($filtros['id_asignado'])) {
            $builder->where('id_asignado', $filtros['id_asignado']);
        }
        if (!empty($filtros['id_creador'])) {
            $builder->where('id_creador', $filtros['id_creador']);
        }
        if (!empty($filtros['prioridad'])) {
            $builder->where('prioridad', $filtros['prioridad']);
        }
        if (!empty($filtros['id_categoria'])) {
            $builder->where('id_categoria', $filtros['id_categoria']);
        }
        // Filtro por rango de fecha limite
        if (!empty($filtros['fecha_limite_desde'])) {
            $builder->where('fecha_limite >=', $filtros['fecha_limite_desde']);
        }
        if (!empty($filtros['fecha_limite_hasta'])) {
            $builder->where('fecha_limite <=', $filtros['fecha_limite_hasta']);
        }
        // Filtro por rango de fecha creacion
        if (!empty($filtros['fecha_creacion_desde'])) {
            $builder->where('fecha_creacion >=', $filtros['fecha_creacion_desde']);
        }
        if (!empty($filtros['fecha_creacion_hasta'])) {
            $builder->where('fecha_creacion <=', $filtros['fecha_creacion_hasta'] . ' 23:59:59');
        }
        // Busqueda por texto (titulo o codigo)
        if (!empty($filtros['busqueda'])) {
            $builder->groupStart()
                    ->like('titulo', $filtros['busqueda'])
                    ->orLike('codigo', $filtros['busqueda'])
                    ->groupEnd();
        }
        // Filtro para actividades vencidas
        if (!empty($filtros['vencidas'])) {
            $builder->where('dias_restantes <', 0)
                    ->whereNotIn('estado', ['completada', 'cancelada']);
        }
        // Filtro para actividades proximas a vencer (7 dias)
        if (!empty($filtros['proximas_vencer'])) {
            $builder->where('dias_restantes >=', 0)
                    ->where('dias_restantes <=', 7)
                    ->whereNotIn('estado', ['completada', 'cancelada']);
        }

        return $builder->orderBy('fecha_creacion', 'DESC')->get()->getResultArray();
    }

    /**
     * Obtiene conteos para el resumen del tablero
     */
    public function getResumenTablero()
    {
        $todas = $this->db->table('vw_tablero_actividades')->get()->getResultArray();

        $resumen = [
            'total' => count($todas),
            'por_estado' => [
                'pendiente' => 0,
                'en_progreso' => 0,
                'en_revision' => 0,
                'completada' => 0,
                'cancelada' => 0
            ],
            'vencidas' => 0,
            'proximas_vencer' => 0,
            'por_responsable' => []
        ];

        foreach ($todas as $act) {
            // Conteo por estado
            $resumen['por_estado'][$act['estado']]++;

            // Conteo de vencidas (no completadas ni canceladas)
            if (!empty($act['dias_restantes']) && $act['dias_restantes'] < 0
                && !in_array($act['estado'], ['completada', 'cancelada'])) {
                $resumen['vencidas']++;
            }

            // Conteo proximas a vencer (7 dias)
            if (isset($act['dias_restantes']) && $act['dias_restantes'] >= 0 && $act['dias_restantes'] <= 7
                && !in_array($act['estado'], ['completada', 'cancelada'])) {
                $resumen['proximas_vencer']++;
            }

            // Conteo por responsable
            $idAsignado = $act['id_asignado'] ?? 0;
            $nombreAsignado = $act['nombre_asignado'] ?? 'Sin asignar';
            if (!isset($resumen['por_responsable'][$idAsignado])) {
                $resumen['por_responsable'][$idAsignado] = [
                    'id' => $idAsignado,
                    'nombre' => $nombreAsignado,
                    'total' => 0,
                    'activas' => 0
                ];
            }
            $resumen['por_responsable'][$idAsignado]['total']++;
            if (!in_array($act['estado'], ['completada', 'cancelada'])) {
                $resumen['por_responsable'][$idAsignado]['activas']++;
            }
        }

        // Ordenar responsables por cantidad de tareas activas
        uasort($resumen['por_responsable'], fn($a, $b) => $b['activas'] - $a['activas']);

        return $resumen;
    }

    /**
     * Obtiene actividades agrupadas por estado para tablero Kanban
     */
    public function getActividadesPorEstado($filtros = [])
    {
        $actividades = $this->getActividadesCompletas($filtros);

        $tablero = [
            'pendiente'    => [],
            'en_progreso'  => [],
            'en_revision'  => [],
            'completada'   => [],
            'cancelada'    => []
        ];

        foreach ($actividades as $act) {
            $tablero[$act['estado']][] = $act;
        }

        return $tablero;
    }

    /**
     * Obtiene actividades agrupadas por responsable
     */
    public function getActividadesPorResponsable($filtros = [])
    {
        $actividades = $this->getActividadesCompletas($filtros);

        $porResponsable = [];

        foreach ($actividades as $act) {
            $idAsignado = $act['id_asignado'] ?? 0;
            $nombreAsignado = $act['nombre_asignado'] ?? 'Sin asignar';

            if (!isset($porResponsable[$idAsignado])) {
                $porResponsable[$idAsignado] = [
                    'id_usuario' => $idAsignado,
                    'nombre' => $nombreAsignado,
                    'actividades' => []
                ];
            }

            $porResponsable[$idAsignado]['actividades'][] = $act;
        }

        return $porResponsable;
    }

    /**
     * Obtiene estadísticas generales
     */
    public function getEstadisticas()
    {
        return $this->db->table('vw_estadisticas_actividades')
                        ->get()
                        ->getResultArray();
    }

    /**
     * Cambia el estado de una actividad y registra en historial
     */
    public function cambiarEstado($idActividad, $nuevoEstado, $idUsuario)
    {
        $actividad = $this->find($idActividad);
        if (!$actividad) {
            return false;
        }

        $estadoAnterior = $actividad['estado'];

        $dataUpdate = ['estado' => $nuevoEstado];

        if ($nuevoEstado === 'en_progreso' && empty($actividad['fecha_inicio'])) {
            $dataUpdate['fecha_inicio'] = date('Y-m-d H:i:s');
        }

        if (in_array($nuevoEstado, ['completada', 'cancelada'])) {
            $dataUpdate['fecha_cierre'] = date('Y-m-d H:i:s');
            if ($nuevoEstado === 'completada') {
                $dataUpdate['porcentaje_avance'] = 100;
            }
        }

        // Ajustar porcentaje de avance automáticamente según estado
        $porcentajeActual = (int) ($actividad['porcentaje_avance'] ?? 0);

        switch ($nuevoEstado) {
            case 'pendiente':
                // Si viene de completada o tiene 100%, resetear a 0
                if ($estadoAnterior === 'completada' || $porcentajeActual === 100) {
                    $dataUpdate['porcentaje_avance'] = 0;
                }
                // Limpiar fecha de cierre si se regresa a pendiente
                $dataUpdate['fecha_cierre'] = null;
                break;

            case 'en_progreso':
                // Si viene de completada o pendiente con 0/100%, poner en 25%
                if ($porcentajeActual === 0 || $porcentajeActual === 100) {
                    $dataUpdate['porcentaje_avance'] = 25;
                }
                // Limpiar fecha de cierre si se regresa a en_progreso
                $dataUpdate['fecha_cierre'] = null;
                break;

            case 'en_revision':
                // En revisión debería estar entre 75-90%, nunca 100%
                if ($porcentajeActual < 75 || $porcentajeActual === 100) {
                    $dataUpdate['porcentaje_avance'] = 90;
                }
                // Limpiar fecha de cierre si se regresa a en_revision
                $dataUpdate['fecha_cierre'] = null;
                break;

            case 'completada':
                // Ya manejado arriba: porcentaje = 100
                break;

            case 'cancelada':
                // Mantener el porcentaje actual (ya manejado arriba con fecha_cierre)
                break;
        }

        $this->update($idActividad, $dataUpdate);

        // Registrar en historial
        $historialModel = new ActividadHistorialModel();
        $historialModel->insert([
            'id_actividad'   => $idActividad,
            'id_usuario'     => $idUsuario,
            'campo'          => 'estado',
            'valor_anterior' => $estadoAnterior,
            'valor_nuevo'    => $nuevoEstado,
            'created_at'     => date('Y-m-d H:i:s')
        ]);

        return true;
    }
}
