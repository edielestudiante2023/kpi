<?php
namespace App\Models;

use CodeIgniter\Model;

class HistorialIndicadorModel extends Model
{
    protected $table            = 'historial_indicadores';
    protected $primaryKey       = 'id_historial';
    protected $allowedFields    = [
        'id_indicador_perfil', 'id_usuario', 'periodo', 'valores_json', 'resultado_real', 'comentario', 'fecha_registro', 'cumple'
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'fecha_registro';
    protected $updatedField     = '';

   public function insertarSinDuplicar($data)
{
    $existe = $this->where('id_indicador_perfil', $data['id_indicador_perfil'])
                   ->where('id_usuario', $data['id_usuario'])
                   ->where('periodo', $data['periodo'])
                   ->first();

    if (! $existe) {
        return $this->insert($data);
    } else {
        return $this->update($existe['id_historial'], $data);
    }
}


}
