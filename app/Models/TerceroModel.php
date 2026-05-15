<?php

namespace App\Models;

use CodeIgniter\Model;

class TerceroModel extends Model
{
    protected $table         = 'tbl_terceros';
    protected $primaryKey    = 'id_tercero';
    protected $allowedFields = [
        'tipo_documento', 'documento', 'nombre', 'email', 'telefono',
        'banco', 'tipo_cuenta', 'numero_cuenta', 'titular_cuenta',
        'activo', 'notas', 'creado_por',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Listado con conteo de documentos adjuntos por tipo.
     */
    public function getListadoConDocumentos(?string $busqueda = null, bool $soloActivos = false): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' t')
            ->select("
                t.*,
                COALESCE(SUM(CASE WHEN d.tipo = 'rut' THEN 1 ELSE 0 END), 0) AS tiene_rut,
                COALESCE(SUM(CASE WHEN d.tipo = 'cedula' THEN 1 ELSE 0 END), 0) AS tiene_cedula,
                COALESCE(SUM(CASE WHEN d.tipo = 'cert_bancaria' THEN 1 ELSE 0 END), 0) AS tiene_cert_bancaria
            ", false)
            ->join('tbl_terceros_documentos d', 'd.id_tercero = t.id_tercero', 'left')
            ->groupBy('t.id_tercero')
            ->orderBy('t.nombre', 'ASC');

        if ($soloActivos) $b->where('t.activo', 1);
        if ($busqueda) {
            $b->groupStart()
                ->like('t.nombre', $busqueda)
                ->orLike('t.documento', $busqueda)
                ->orLike('t.email', $busqueda)
            ->groupEnd();
        }

        return $b->get()->getResultArray();
    }

    /**
     * Cuenta de cobros asociadas a un tercero (para impedir borrado si tiene).
     */
    public function contarCuentasCobro(int $idTercero): int
    {
        $db = \Config\Database::connect();
        return (int) $db->table('tbl_cuenta_cobro')
            ->where('id_tercero', $idTercero)
            ->countAllResults();
    }
}
