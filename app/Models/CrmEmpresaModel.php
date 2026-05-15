<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmEmpresaModel extends Model
{
    protected $table         = 'tbl_crm_empresa';
    protected $primaryKey    = 'id_empresa';
    protected $allowedFields = [
        'razon_social', 'nit', 'sector', 'tamano', 'ciudad', 'telefono',
        'email_principal', 'sitio_web', 'id_fuente', 'id_responsable',
        'notas', 'activo', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Listado con join al responsable y nombre de fuente.
     */
    public function getListado(?string $busqueda = null, bool $soloActivas = true): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' e')
            ->select('e.*, u.nombre_completo AS responsable_nombre, f.nombre AS fuente_nombre')
            ->join('users u', 'u.id_users = e.id_responsable', 'left')
            ->join('tbl_crm_fuente f', 'f.id_fuente = e.id_fuente', 'left')
            ->orderBy('e.razon_social', 'ASC');

        if ($soloActivas) $b->where('e.activo', 1);
        if ($busqueda) {
            $b->groupStart()
                ->like('e.razon_social', $busqueda)
                ->orLike('e.nit', $busqueda)
                ->orLike('e.email_principal', $busqueda)
                ->orLike('e.ciudad', $busqueda)
            ->groupEnd();
        }
        return $b->get()->getResultArray();
    }

    /**
     * Búsqueda AJAX (Select2): devuelve id + label.
     */
    public function buscarAjax(string $q): array
    {
        if (strlen($q) < 2) return [];
        return $this->select('id_empresa, razon_social, nit')
                    ->where('activo', 1)
                    ->groupStart()
                        ->like('razon_social', $q)
                        ->orLike('nit', $q)
                    ->groupEnd()
                    ->orderBy('razon_social', 'ASC')
                    ->limit(20)
                    ->findAll();
    }

    /**
     * Conteo de oportunidades por empresa (para impedir borrado si tiene).
     */
    public function contarOportunidades(int $idEmpresa): int
    {
        $db = \Config\Database::connect();
        return (int) $db->table('tbl_crm_oportunidad')
            ->where('id_empresa', $idEmpresa)
            ->countAllResults();
    }
}
