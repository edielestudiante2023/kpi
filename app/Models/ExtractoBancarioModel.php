<?php

namespace App\Models;

use CodeIgniter\Model;

class ExtractoBancarioModel extends Model
{
    protected $table         = 'tbl_extractos_bancarios';
    protected $primaryKey    = 'id_extracto';
    protected $allowedFields = [
        'id_cuenta_banco', 'anio', 'mes', 'descripcion',
        'nombre_original', 'ruta_pdf', 'hash_pdf', 'tamano_pdf', 'subido_por',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Listado de extractos con nombre de cuenta, filtrable por año / cuenta.
     */
    public function getListado(?int $anio = null, ?int $idCuentaBanco = null): array
    {
        $db = \Config\Database::connect();
        $b = $db->table($this->table . ' e')
            ->select('e.*, cb.nombre_cuenta')
            ->join('tbl_cuentas_banco cb', 'cb.id_cuenta_banco = e.id_cuenta_banco', 'left')
            ->orderBy('e.anio', 'DESC')
            ->orderBy('e.mes', 'DESC')
            ->orderBy('e.created_at', 'DESC');

        if ($anio)          $b->where('e.anio', $anio);
        if ($idCuentaBanco) $b->where('e.id_cuenta_banco', $idCuentaBanco);

        return $b->get()->getResultArray();
    }

    /**
     * Años con extractos cargados (para filtro).
     */
    public function getAniosDisponibles(): array
    {
        $rows = $this->select('anio')->distinct()->orderBy('anio', 'DESC')->findAll();
        return array_column($rows, 'anio');
    }
}
