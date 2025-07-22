<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditoriaIndicadorModel extends Model
{
    protected $table            = 'vw_auditoria_indicadores';
    protected $primaryKey       = 'id_auditoria';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = []; // Es una vista, no se edita

    // Por si necesitas filtrado o paginación en el futuro:
    public function getAllAuditorias()
    {
        return $this->orderBy('fecha_edicion', 'DESC')->findAll();
    }
}
