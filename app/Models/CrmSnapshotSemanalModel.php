<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Snapshot semanal del pipeline: foto congelada del estado del CRM
 * en un momento dado. Sirve para comparar avance semana a semana
 * y alimentar al asistente IA con datos históricos.
 */
class CrmSnapshotSemanalModel extends Model
{
    protected $table         = 'tbl_crm_snapshot_semanal';
    protected $primaryKey    = 'id_snapshot';
    protected $allowedFields = [
        'fecha_corte',
        'total_abiertas', 'valor_pipeline',
        'total_ganadas_anio', 'valor_ganadas_anio',
        'total_perdidas_anio', 'valor_perdidas_anio',
        'tasa_conversion_anio', 'ciclo_promedio_dias',
        'oportunidades_estancadas_30d',
        'por_etapa', 'por_responsable', 'motivos_perdida_top',
        'creado_por', 'notas',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Genera un snapshot del estado actual del pipeline.
     * Calcula todos los KPIs leyendo directamente de las tablas vivas.
     */
    public function generar(int $idCreador, ?string $notas = null): int
    {
        $db = \Config\Database::connect();
        $anio = (int) date('Y');

        // KPIs principales
        $r = $db->table('tbl_crm_oportunidad o')
            ->select("
                COUNT(CASE WHEN et.tipo = 'abierta' THEN 1 END) AS total_abiertas,
                COALESCE(SUM(CASE WHEN et.tipo = 'abierta' THEN o.valor ELSE 0 END), 0) AS valor_pipeline,
                COUNT(CASE WHEN et.tipo = 'ganada'  AND YEAR(o.fecha_cierre_real) = $anio THEN 1 END) AS total_ganadas_anio,
                COALESCE(SUM(CASE WHEN et.tipo = 'ganada'  AND YEAR(o.fecha_cierre_real) = $anio THEN o.valor ELSE 0 END), 0) AS valor_ganadas_anio,
                COUNT(CASE WHEN et.tipo = 'perdida' AND YEAR(o.fecha_cierre_real) = $anio THEN 1 END) AS total_perdidas_anio,
                COALESCE(SUM(CASE WHEN et.tipo = 'perdida' AND YEAR(o.fecha_cierre_real) = $anio THEN o.valor ELSE 0 END), 0) AS valor_perdidas_anio
            ", false)
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->get()->getRowArray() ?: [];

        $cerradas = (int) $r['total_ganadas_anio'] + (int) $r['total_perdidas_anio'];
        $tasaConv = $cerradas > 0 ? round((int) $r['total_ganadas_anio'] / $cerradas * 100, 2) : 0;

        // Ciclo promedio (días entre creación y cierre real, oportunidades cerradas este año)
        $ciclo = $db->table('tbl_crm_oportunidad o')
            ->select("AVG(DATEDIFF(o.fecha_cierre_real, o.created_at)) AS dias_promedio", false)
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->where("et.tipo IN ('ganada','perdida')")
            ->where('o.fecha_cierre_real IS NOT NULL', null, false)
            ->where("YEAR(o.fecha_cierre_real)", $anio)
            ->get()->getRowArray();
        $cicloProm = isset($ciclo['dias_promedio']) ? (int) round((float) $ciclo['dias_promedio']) : null;

        // Estancadas (>30 días sin actividad y aún abiertas)
        $est = $db->table('tbl_crm_oportunidad o')
            ->select('COUNT(*) c', false)
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->where('et.tipo', 'abierta')
            ->where("(o.ultima_actividad_at IS NULL OR o.ultima_actividad_at < DATE_SUB(NOW(), INTERVAL 30 DAY))", null, false)
            ->get()->getRowArray();
        $estancadas = (int) ($est['c'] ?? 0);

        // Breakdown por etapa
        $porEtapa = $db->table('tbl_crm_oportunidad o')
            ->select("et.id_etapa, et.nombre, et.color, et.tipo, et.orden,
                      COUNT(o.id_oportunidad) AS cantidad,
                      COALESCE(SUM(o.valor), 0) AS valor_total")
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->where('et.tipo', 'abierta')
            ->groupBy('et.id_etapa, et.nombre, et.color, et.tipo, et.orden')
            ->orderBy('et.orden', 'ASC')
            ->get()->getResultArray();

        // Breakdown por responsable
        $porResponsable = $db->table('tbl_crm_oportunidad o')
            ->select("u.id_users, u.nombre_completo,
                      COUNT(CASE WHEN et.tipo = 'abierta' THEN 1 END) AS abiertas,
                      COALESCE(SUM(CASE WHEN et.tipo = 'abierta' THEN o.valor ELSE 0 END), 0) AS valor_abierto,
                      COUNT(CASE WHEN et.tipo = 'ganada' AND YEAR(o.fecha_cierre_real) = $anio THEN 1 END) AS ganadas,
                      COALESCE(SUM(CASE WHEN et.tipo = 'ganada' AND YEAR(o.fecha_cierre_real) = $anio THEN o.valor ELSE 0 END), 0) AS valor_ganado")
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->join('users u', 'u.id_users = o.id_responsable')
            ->groupBy('u.id_users, u.nombre_completo')
            ->orderBy('valor_ganado', 'DESC')
            ->get()->getResultArray();

        // Top motivos de pérdida del año
        $motivos = $db->table('tbl_crm_oportunidad o')
            ->select("mp.id_motivo_perdida, mp.nombre,
                      COUNT(o.id_oportunidad) AS cantidad,
                      COALESCE(SUM(o.valor), 0) AS valor_total")
            ->join('tbl_crm_motivo_perdida mp', 'mp.id_motivo_perdida = o.id_motivo_perdida')
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->where('et.tipo', 'perdida')
            ->where("YEAR(o.fecha_cierre_real)", $anio)
            ->groupBy('mp.id_motivo_perdida, mp.nombre')
            ->orderBy('cantidad', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $datos = [
            'fecha_corte'                  => date('Y-m-d H:i:s'),
            'total_abiertas'               => (int) $r['total_abiertas'],
            'valor_pipeline'               => (float) $r['valor_pipeline'],
            'total_ganadas_anio'           => (int) $r['total_ganadas_anio'],
            'valor_ganadas_anio'           => (float) $r['valor_ganadas_anio'],
            'total_perdidas_anio'          => (int) $r['total_perdidas_anio'],
            'valor_perdidas_anio'          => (float) $r['valor_perdidas_anio'],
            'tasa_conversion_anio'         => $tasaConv,
            'ciclo_promedio_dias'          => $cicloProm,
            'oportunidades_estancadas_30d' => $estancadas,
            'por_etapa'                    => json_encode($porEtapa, JSON_UNESCAPED_UNICODE),
            'por_responsable'              => json_encode($porResponsable, JSON_UNESCAPED_UNICODE),
            'motivos_perdida_top'          => json_encode($motivos, JSON_UNESCAPED_UNICODE),
            'creado_por'                   => $idCreador,
            'notas'                        => $notas,
        ];
        return (int) $this->insert($datos, true);
    }

    /**
     * Historial: todos los snapshots con nombre del autor.
     */
    public function getHistorial(int $limit = 100): array
    {
        $db = \Config\Database::connect();
        return $db->table($this->table . ' s')
            ->select('s.*, u.nombre_completo AS autor_nombre')
            ->join('users u', 'u.id_users = s.creado_por', 'left')
            ->orderBy('s.fecha_corte', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Snapshot por id con autor y JSONs ya decodificados.
     */
    public function getConDetalle(int $id): ?array
    {
        $db = \Config\Database::connect();
        $r = $db->table($this->table . ' s')
            ->select('s.*, u.nombre_completo AS autor_nombre')
            ->join('users u', 'u.id_users = s.creado_por', 'left')
            ->where('s.id_snapshot', $id)
            ->get()->getRowArray();
        if (!$r) return null;

        foreach (['por_etapa', 'por_responsable', 'motivos_perdida_top'] as $k) {
            $r[$k] = $r[$k] ? json_decode($r[$k], true) : [];
        }
        return $r;
    }

    /**
     * Snapshot más reciente (para mostrar en dashboard / comparar).
     */
    public function getMasReciente(): ?array
    {
        $r = $this->orderBy('fecha_corte', 'DESC')->first();
        return $r ?: null;
    }
}
