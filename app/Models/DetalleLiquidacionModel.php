<?php

namespace App\Models;

use CodeIgniter\Model;

class DetalleLiquidacionModel extends Model
{
    protected $table         = 'detalle_liquidacion';
    protected $primaryKey    = 'id_detalle';
    protected $allowedFields = [
        'id_liquidacion', 'id_usuario', 'jornada', 'dias_habiles',
        'horas_meta', 'horas_trabajadas', 'porcentaje_cumplimiento',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
