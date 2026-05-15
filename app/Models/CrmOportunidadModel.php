<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmOportunidadModel extends Model
{
    protected $table         = 'tbl_crm_oportunidad';
    protected $primaryKey    = 'id_oportunidad';
    protected $allowedFields = [
        'codigo', 'id_empresa', 'id_contacto_principal', 'titulo', 'descripcion',
        'valor', 'moneda', 'id_etapa', 'probabilidad',
        'fecha_cierre_estimada', 'fecha_cierre_real',
        'id_motivo_perdida', 'id_responsable', 'id_creador',
        'notas', 'notificado_estancada', 'ultima_actividad_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Genera código OPP-YYYYMMDD-#### (mismo patrón que ActividadModel).
     */
    public function generarCodigo(): string
    {
        $fecha = date('Ymd');
        $prefijo = "OPP-{$fecha}-";

        $ultima = $this->like('codigo', $prefijo, 'after')
                       ->orderBy('codigo', 'DESC')
                       ->first();

        $numero = $ultima ? (int) substr($ultima['codigo'], -4) + 1 : 1;
        return $prefijo . str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Query con joins para listado / Kanban / detalle.
     */
    private function builderConJoins()
    {
        $db = \Config\Database::connect();
        return $db->table($this->table . ' o')
            ->select("
                o.*,
                e.razon_social AS empresa_nombre,
                c.nombre       AS contacto_nombre,
                u.nombre_completo AS responsable_nombre,
                et.nombre AS etapa_nombre,
                et.color  AS etapa_color,
                et.tipo   AS etapa_tipo,
                et.orden  AS etapa_orden,
                mp.nombre AS motivo_perdida_nombre
            ")
            ->join('tbl_crm_empresa e', 'e.id_empresa = o.id_empresa', 'left')
            ->join('tbl_crm_contacto c', 'c.id_contacto = o.id_contacto_principal', 'left')
            ->join('users u', 'u.id_users = o.id_responsable', 'left')
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa', 'left')
            ->join('tbl_crm_motivo_perdida mp', 'mp.id_motivo_perdida = o.id_motivo_perdida', 'left');
    }

    /**
     * Aplica filtro de visibilidad: admins ven todo; el resto sólo sus propias.
     * Centralizado para que ningún query lo olvide.
     */
    private function aplicarVisibilidad($builder, int $idUsuario, bool $esAdmin)
    {
        if (!$esAdmin) {
            $builder->where('o.id_responsable', $idUsuario);
        }
        return $builder;
    }

    /**
     * Listado completo (para DataTable).
     */
    public function getListadoVisible(int $idUsuario, bool $esAdmin, array $filtros = []): array
    {
        $b = $this->builderConJoins();
        $this->aplicarVisibilidad($b, $idUsuario, $esAdmin);

        if (!empty($filtros['id_etapa']))       $b->where('o.id_etapa', (int) $filtros['id_etapa']);
        if (!empty($filtros['id_responsable'])) $b->where('o.id_responsable', (int) $filtros['id_responsable']);
        if (!empty($filtros['id_empresa']))     $b->where('o.id_empresa', (int) $filtros['id_empresa']);
        if (!empty($filtros['desde']))          $b->where('o.created_at >=', $filtros['desde']);
        if (!empty($filtros['hasta']))          $b->where('o.created_at <=', $filtros['hasta'] . ' 23:59:59');

        return $b->orderBy('o.created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * Oportunidades agrupadas por etapa, para Kanban.
     * Retorna [id_etapa => [oportunidad, oportunidad, ...], ...].
     */
    public function getKanban(int $idUsuario, bool $esAdmin): array
    {
        $b = $this->builderConJoins();
        $this->aplicarVisibilidad($b, $idUsuario, $esAdmin);
        $rows = $b->orderBy('et.orden', 'ASC')
                  ->orderBy('o.updated_at', 'DESC')
                  ->get()->getResultArray();

        $agrupado = [];
        foreach ($rows as $r) {
            $agrupado[(int) $r['id_etapa']][] = $r;
        }
        return $agrupado;
    }

    /**
     * Una oportunidad con joins (para vista detalle).
     */
    public function getConJoins(int $id): ?array
    {
        $b = $this->builderConJoins();
        $r = $b->where('o.id_oportunidad', $id)->get()->getRowArray();
        return $r ?: null;
    }

    /**
     * Cambia la etapa de una oportunidad y registra historial.
     * Devuelve true si cambió, false si la etapa solicitada es la misma.
     */
    public function cambiarEtapa(int $idOportunidad, int $idEtapaNueva, int $idUsuario, ?string $comentario = null): bool
    {
        $op = $this->find($idOportunidad);
        if (!$op) return false;
        if ((int) $op['id_etapa'] === $idEtapaNueva) return false;

        $idEtapaAnterior = (int) $op['id_etapa'];

        // Detectar si la nueva etapa es de cierre (ganada/perdida) para llenar fecha_cierre_real
        $db = \Config\Database::connect();
        $etapaNueva = $db->table('tbl_crm_etapa')->where('id_etapa', $idEtapaNueva)->get()->getRowArray();
        $update = [
            'id_etapa'     => $idEtapaNueva,
            'probabilidad' => (int) ($etapaNueva['probabilidad_default'] ?? $op['probabilidad']),
        ];
        if (in_array(($etapaNueva['tipo'] ?? 'abierta'), ['ganada', 'perdida'], true)) {
            $update['fecha_cierre_real'] = date('Y-m-d');
        } else {
            // Si vuelve a abierta, limpiar fecha_cierre_real
            $update['fecha_cierre_real'] = null;
            $update['id_motivo_perdida'] = null;
        }

        $this->update($idOportunidad, $update);

        $db->table('tbl_crm_oportunidad_historial')->insert([
            'id_oportunidad'    => $idOportunidad,
            'id_etapa_anterior' => $idEtapaAnterior,
            'id_etapa_nueva'    => $idEtapaNueva,
            'id_usuario'        => $idUsuario,
            'comentario'        => $comentario,
        ]);

        return true;
    }

    /**
     * Funnel: cantidad y valor por etapa (sólo etapas abiertas, visible al usuario).
     */
    public function getFunnel(int $idUsuario, bool $esAdmin): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' o')
            ->select("et.id_etapa, et.nombre, et.color, et.orden,
                      COUNT(o.id_oportunidad) AS cantidad,
                      COALESCE(SUM(o.valor), 0) AS valor_total")
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->where('et.tipo', 'abierta')
            ->groupBy('et.id_etapa, et.nombre, et.color, et.orden')
            ->orderBy('et.orden', 'ASC');
        $this->aplicarVisibilidad($b, $idUsuario, $esAdmin);
        return $b->get()->getResultArray();
    }

    /**
     * Métricas globales para tarjetas del dashboard.
     */
    public function getMetricas(int $idUsuario, bool $esAdmin): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' o')
            ->select("
                COUNT(CASE WHEN et.tipo = 'abierta' THEN 1 END) AS abiertas,
                COALESCE(SUM(CASE WHEN et.tipo = 'abierta' THEN o.valor ELSE 0 END), 0) AS valor_pipeline,
                COUNT(CASE WHEN et.tipo = 'ganada'  THEN 1 END) AS ganadas,
                COALESCE(SUM(CASE WHEN et.tipo = 'ganada'  THEN o.valor ELSE 0 END), 0) AS valor_ganado,
                COUNT(CASE WHEN et.tipo = 'perdida' THEN 1 END) AS perdidas
            ")
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa');
        $this->aplicarVisibilidad($b, $idUsuario, $esAdmin);
        $r = $b->get()->getRowArray() ?: [];

        $ganadas  = (int) ($r['ganadas'] ?? 0);
        $perdidas = (int) ($r['perdidas'] ?? 0);
        $cerradas = $ganadas + $perdidas;
        $r['tasa_conversion'] = $cerradas > 0 ? round(($ganadas / $cerradas) * 100, 1) : 0;
        return $r;
    }

    /**
     * Won/Lost de los últimos N meses (para gráfico de línea).
     */
    public function getWonLostUltimosMeses(int $idUsuario, bool $esAdmin, int $meses = 6): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' o')
            ->select("
                DATE_FORMAT(o.fecha_cierre_real, '%Y-%m') AS periodo,
                SUM(CASE WHEN et.tipo = 'ganada'  THEN 1 ELSE 0 END) AS ganadas,
                SUM(CASE WHEN et.tipo = 'perdida' THEN 1 ELSE 0 END) AS perdidas,
                SUM(CASE WHEN et.tipo = 'ganada'  THEN o.valor ELSE 0 END) AS valor_ganado
            ")
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->where('et.tipo !=', 'abierta')
            ->where('o.fecha_cierre_real >=', date('Y-m-01', strtotime("-{$meses} months")))
            ->groupBy('periodo')
            ->orderBy('periodo', 'ASC');
        $this->aplicarVisibilidad($b, $idUsuario, $esAdmin);
        return $b->get()->getResultArray();
    }

    /**
     * Ranking por responsable (valor ganado).
     */
    public function getRankingResponsables(int $idUsuario, bool $esAdmin, int $limit = 10): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' o')
            ->select("u.id_users, u.nombre_completo,
                      COUNT(CASE WHEN et.tipo = 'ganada' THEN 1 END) AS ganadas,
                      COALESCE(SUM(CASE WHEN et.tipo = 'ganada' THEN o.valor ELSE 0 END), 0) AS valor_ganado,
                      COUNT(CASE WHEN et.tipo = 'abierta' THEN 1 END) AS abiertas")
            ->join('tbl_crm_etapa et', 'et.id_etapa = o.id_etapa')
            ->join('users u', 'u.id_users = o.id_responsable')
            ->groupBy('u.id_users, u.nombre_completo')
            ->orderBy('valor_ganado', 'DESC')
            ->limit($limit);
        $this->aplicarVisibilidad($b, $idUsuario, $esAdmin);
        return $b->get()->getResultArray();
    }

    /**
     * Actualiza el timestamp de última actividad (llamado por CrmInteraccionModel).
     */
    public function tocarUltimaActividad(int $idOportunidad): void
    {
        $this->update($idOportunidad, ['ultima_actividad_at' => date('Y-m-d H:i:s')]);
    }
}
