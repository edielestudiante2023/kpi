<?php namespace App\Models;

use CodeIgniter\Model;

class FacturacionCrudaModel extends Model
{
    protected $table      = 'tbl_facturacion_cruda';
    protected $primaryKey = 'id_facturacion_cruda';

    protected $allowedFields = [
        'comprobante', 'fecha_elaboracion', 'identificacion', 'sucursal',
        'nombre_tercero', 'base_gravada', 'base_exenta', 'iva',
        'impoconsumo', 'ad_valorem', 'cargo_en_totales',
        'descuento_en_totales', 'total',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
