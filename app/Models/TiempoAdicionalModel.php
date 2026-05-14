<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tiempo adicional acumulado por quincena.
 *
 * Cada fila = el excedente (horas_trabajadas - horas_meta) de un usuario en
 * una quincena liquidada. Se inserta automáticamente al ejecutar la liquidación.
 * El consumo de ese saldo se hace vía novedades individuales de tipo
 * 'uso_tiempo_adicional' (ver NovedadIndividualModel).
 */
class TiempoAdicionalModel extends Model
{
    protected $table         = 'tiempo_adicional_quincena';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['id_usuario', 'id_liquidacion', 'horas_adicionales'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Total de horas acumuladas (sin descontar consumos) de un usuario.
     */
    public function getAcumuladoUsuario(int $idUsuario): float
    {
        $r = $this->selectSum('horas_adicionales', 'total')
                  ->where('id_usuario', $idUsuario)
                  ->first();
        return (float) ($r['total'] ?? 0);
    }

    /**
     * Total acumulado de todos los usuarios → [id_usuario => horas].
     */
    public function getAcumuladoTodos(): array
    {
        $rows = $this->select('id_usuario, SUM(horas_adicionales) AS total')
                     ->groupBy('id_usuario')
                     ->findAll();
        $mapa = [];
        foreach ($rows as $r) {
            $mapa[(int) $r['id_usuario']] = (float) $r['total'];
        }
        return $mapa;
    }

    /**
     * Detalle de acumulaciones de un usuario, con el periodo de cada quincena.
     */
    public function getAcumulacionesUsuario(int $idUsuario): array
    {
        return $this->select('tiempo_adicional_quincena.*, l.fecha_inicio, l.fecha_corte')
                    ->join('liquidaciones_bitacora l', 'l.id_liquidacion = tiempo_adicional_quincena.id_liquidacion')
                    ->where('tiempo_adicional_quincena.id_usuario', $idUsuario)
                    ->orderBy('l.fecha_corte', 'DESC')
                    ->findAll();
    }
}
