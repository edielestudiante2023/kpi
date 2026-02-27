<?php

namespace App\Models;

use CodeIgniter\Model;

class LiquidacionModel extends Model
{
    protected $table         = 'liquidaciones_bitacora';
    protected $primaryKey    = 'id_liquidacion';
    protected $allowedFields = ['fecha_inicio', 'fecha_corte', 'dias_habiles', 'ejecutado_por', 'notas'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getUltimaLiquidacion(): ?array
    {
        $result = $this->orderBy('fecha_corte', 'DESC')->first();
        return $result ?: null;
    }

    public function getHistorial(): array
    {
        return $this->select('liquidaciones_bitacora.*, u.nombre_completo AS ejecutor')
                    ->join('users u', 'u.id_users = liquidaciones_bitacora.ejecutado_por')
                    ->orderBy('fecha_corte', 'DESC')
                    ->findAll();
    }

    public function getDetalle(int $idLiquidacion): array
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT dl.*, u.nombre_completo, u.correo, u.cargo
            FROM detalle_liquidacion dl
            JOIN users u ON u.id_users = dl.id_usuario
            WHERE dl.id_liquidacion = ?
            ORDER BY dl.porcentaje_cumplimiento DESC
        ", [$idLiquidacion])->getResultArray();
    }
}
