<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmContactoModel extends Model
{
    protected $table         = 'tbl_crm_contacto';
    protected $primaryKey    = 'id_contacto';
    protected $allowedFields = [
        'id_empresa', 'nombre', 'cargo', 'email', 'telefono',
        'es_decisor', 'notas', 'activo',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Contactos de una empresa (activos por defecto).
     */
    public function getDeEmpresa(int $idEmpresa, bool $soloActivos = true): array
    {
        $b = $this->where('id_empresa', $idEmpresa);
        if ($soloActivos) $b->where('activo', 1);
        return $b->orderBy('es_decisor', 'DESC')->orderBy('nombre', 'ASC')->findAll();
    }
}
