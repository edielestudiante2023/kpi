<?php

namespace App\Models;

use CodeIgniter\Model;

class NovedadIndividualModel extends Model
{
    protected $table         = 'novedades_individuales';
    protected $primaryKey    = 'id_novedad_individual';
    protected $allowedFields = ['id_usuario', 'fecha', 'horas_reduccion', 'motivo', 'tipo', 'created_by'];
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

    /**
     * Horas consumidas de tiempo adicional por un usuario (novedades tipo 'uso_tiempo_adicional').
     */
    public function getHorasConsumidasUsuario(int $idUsuario): float
    {
        $r = $this->selectSum('horas_reduccion', 'total')
                  ->where('id_usuario', $idUsuario)
                  ->where('tipo', 'uso_tiempo_adicional')
                  ->first();
        return (float) ($r['total'] ?? 0);
    }

    /**
     * Horas consumidas de tiempo adicional de todos los usuarios → [id_usuario => horas].
     */
    public function getConsumidoTodos(): array
    {
        $rows = $this->select('id_usuario, SUM(horas_reduccion) AS total')
                     ->where('tipo', 'uso_tiempo_adicional')
                     ->groupBy('id_usuario')
                     ->findAll();
        $mapa = [];
        foreach ($rows as $r) {
            $mapa[(int) $r['id_usuario']] = (float) $r['total'];
        }
        return $mapa;
    }

    /**
     * Detalle de consumos de tiempo adicional de un usuario.
     */
    public function getConsumosUsuario(int $idUsuario): array
    {
        return $this->where('id_usuario', $idUsuario)
                    ->where('tipo', 'uso_tiempo_adicional')
                    ->orderBy('fecha', 'DESC')
                    ->findAll();
    }
}
