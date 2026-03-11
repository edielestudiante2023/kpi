<?php

namespace App\Models;

use CodeIgniter\Model;

class NovedadIndividualModel extends Model
{
    protected $table         = 'novedades_individuales';
    protected $primaryKey    = 'id_novedad_individual';
    protected $allowedFields = ['id_usuario', 'fecha', 'horas_reduccion', 'motivo', 'created_by'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Suma de horas individuales de un usuario en un rango.
     */
    public function getHorasIndividualesRango(int $idUsuario, string $desde, string $hasta): float
    {
        $result = $this->selectSum('horas_reduccion', 'total')
                       ->where('id_usuario', $idUsuario)
                       ->where('fecha >=', substr($desde, 0, 10))
                       ->where('fecha <=', substr($hasta, 0, 10))
                       ->first();
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Listado con nombre de usuario (para vista admin).
     */
    public function getNovedadesRango(string $desde, string $hasta): array
    {
        return $this->select('novedades_individuales.*, u.nombre_completo')
                    ->join('users u', 'u.id_users = novedades_individuales.id_usuario')
                    ->where('fecha >=', substr($desde, 0, 10))
                    ->where('fecha <=', substr($hasta, 0, 10))
                    ->orderBy('fecha', 'ASC')
                    ->findAll();
    }
}
