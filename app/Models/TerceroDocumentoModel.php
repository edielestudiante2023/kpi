<?php

namespace App\Models;

use CodeIgniter\Model;

class TerceroDocumentoModel extends Model
{
    protected $table         = 'tbl_terceros_documentos';
    protected $primaryKey    = 'id_documento';
    protected $allowedFields = [
        'id_tercero', 'tipo', 'nombre_original',
        'ruta_pdf', 'hash_pdf', 'tamano_pdf', 'subido_por',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Documentos de un tercero ordenados por tipo y fecha (más reciente primero).
     */
    public function getDocumentosTercero(int $idTercero): array
    {
        return $this->where('id_tercero', $idTercero)
                    ->orderBy('tipo', 'ASC')
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}
