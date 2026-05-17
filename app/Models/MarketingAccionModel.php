<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketingAccionModel extends Model
{
    protected $table         = 'tbl_marketing_accion';
    protected $primaryKey    = 'id_accion';
    protected $allowedFields = [
        'fecha', 'id_tipo_accion', 'descripcion', 'costo',
        'leads_generados', 'notas', 'id_responsable',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Listado con joins por tipo y responsable, filtros opcionales.
     */
    public function getListado(array $filtros = []): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' a')
            ->select('a.*, t.nombre AS tipo_nombre, t.color AS tipo_color, u.nombre_completo AS responsable_nombre')
            ->join('tbl_marketing_tipo_accion t', 't.id_tipo_accion = a.id_tipo_accion')
            ->join('users u', 'u.id_users = a.id_responsable', 'left')
            ->orderBy('a.fecha', 'DESC')
            ->orderBy('a.created_at', 'DESC');

        if (!empty($filtros['desde'])) $b->where('a.fecha >=', $filtros['desde']);
        if (!empty($filtros['hasta'])) $b->where('a.fecha <=', $filtros['hasta']);
        if (!empty($filtros['id_tipo_accion'])) $b->where('a.id_tipo_accion', (int) $filtros['id_tipo_accion']);
        if (!empty($filtros['id_responsable'])) $b->where('a.id_responsable', (int) $filtros['id_responsable']);

        return $b->get()->getResultArray();
    }

    /**
     * Conteo y costo total por tipo en un rango (para gráficos).
     */
    public function getResumenPorTipo(string $desde, string $hasta): array
    {
        $db = \Config\Database::connect();
        return $db->table($this->table . ' a')
            ->select('t.id_tipo_accion, t.nombre AS tipo_nombre, t.color AS tipo_color,
                      COUNT(*) AS cantidad,
                      COALESCE(SUM(a.costo), 0) AS costo_total,
                      COALESCE(SUM(a.leads_generados), 0) AS leads_generados_total')
            ->join('tbl_marketing_tipo_accion t', 't.id_tipo_accion = a.id_tipo_accion')
            ->where('a.fecha >=', $desde)
            ->where('a.fecha <=', $hasta)
            ->groupBy('t.id_tipo_accion, t.nombre, t.color')
            ->orderBy('cantidad', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Acciones de una semana específica.
     */
    public function getDeRango(string $desde, string $hasta): array
    {
        return $this->getListado(['desde' => $desde, 'hasta' => $hasta]);
    }
}
