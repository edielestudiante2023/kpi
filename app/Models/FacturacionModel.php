<?php namespace App\Models;

use CodeIgniter\Model;

class FacturacionModel extends Model
{
    protected $table      = 'tbl_facturacion';
    protected $primaryKey = 'id_facturacion';

    protected $allowedFields = [
        'id_portafolio', 'semana', 'fecha_pago', 'mes_pago', 'valor_pagado',
        'dif_facturado_pagado', 'valor_esperado_recaudo_iva', 'retencion_renta_4',
        'base_gravable_neta', 'pagado', 'anio', 'mes', 'extrae',
        'fecha_anticipo', 'anticipo', 'comprobante', 'fecha_elaboracion',
        'identificacion', 'sucursal', 'nombre_tercero', 'base_gravada',
        'base_exenta', 'iva', 'retefuente_4', 'recompra', 'cargo_en_totales',
        'descuento_en_totales', 'total', 'vendedor', 'base_comisiones',
        'numero_factura', 'portafolio_detallado', 'fecha_vence',
    ];

    protected $returnType    = 'array';
    protected $useTimestamps = false;
}
