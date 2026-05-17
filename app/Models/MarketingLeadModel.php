<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketingLeadModel extends Model
{
    protected $table         = 'tbl_marketing_lead';
    protected $primaryKey    = 'id_lead';
    protected $allowedFields = [
        'nombre', 'empresa_text', 'cargo', 'email', 'telefono',
        'id_fuente', 'estado', 'id_responsable',
        'id_empresa_convertida', 'id_oportunidad_convertida',
        'notas', 'fecha_calificacion', 'fecha_descartado', 'motivo_descarte',
        'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Listado con joins: fuente, responsable, empresa convertida.
     */
    public function getListado(array $filtros = []): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' l')
            ->select("
                l.*,
                f.nombre AS fuente_nombre,
                u.nombre_completo AS responsable_nombre,
                e.razon_social AS empresa_convertida_nombre,
                o.codigo AS oportunidad_convertida_codigo
            ")
            ->join('tbl_crm_fuente f', 'f.id_fuente = l.id_fuente', 'left')
            ->join('users u', 'u.id_users = l.id_responsable', 'left')
            ->join('tbl_crm_empresa e', 'e.id_empresa = l.id_empresa_convertida', 'left')
            ->join('tbl_crm_oportunidad o', 'o.id_oportunidad = l.id_oportunidad_convertida', 'left')
            ->orderBy('l.created_at', 'DESC');

        if (!empty($filtros['estado']))         $b->where('l.estado', $filtros['estado']);
        if (!empty($filtros['id_fuente']))      $b->where('l.id_fuente', (int) $filtros['id_fuente']);
        if (!empty($filtros['id_responsable'])) $b->where('l.id_responsable', (int) $filtros['id_responsable']);
        if (!empty($filtros['busqueda'])) {
            $b->groupStart()
                ->like('l.nombre', $filtros['busqueda'])
                ->orLike('l.empresa_text', $filtros['busqueda'])
                ->orLike('l.email', $filtros['busqueda'])
            ->groupEnd();
        }
        return $b->get()->getResultArray();
    }

    /**
     * Conteo agregado por estado para el dashboard.
     */
    public function getConteoPorEstado(): array
    {
        $rows = $this->select('estado, COUNT(*) AS cantidad')
                     ->groupBy('estado')->findAll();
        $mapa = ['nuevo' => 0, 'contactado' => 0, 'calificado' => 0, 'descartado' => 0];
        foreach ($rows as $r) $mapa[$r['estado']] = (int) $r['cantidad'];
        return $mapa;
    }

    /**
     * Leads nuevos en un rango (para series semanales).
     */
    public function contarNuevosEnRango(string $desde, string $hasta): int
    {
        return (int) $this->where('created_at >=', $desde)
                          ->where('created_at <=', $hasta)
                          ->countAllResults();
    }
}
