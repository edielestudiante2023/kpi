<?php namespace App\Models;

use CodeIgniter\Model;

class CuentaCobroModel extends Model
{
    protected $table      = 'tbl_cuenta_cobro';
    protected $primaryKey = 'id_cuenta_cobro';

    protected $allowedFields = [
        'tipo_documento', 'documento', 'nombre_cobrador',
        'email_cobrador', 'telefono_cobrador',
        'id_centro_costo', 'id_clasificacion',
        'descripcion_servicio', 'fecha_gasto', 'periodo_desde', 'periodo_hasta',
        'valor_bruto', 'retencion_fuente', 'retencion_iva', 'retencion_ica',
        'otras_deducciones', 'valor_neto_a_pagar',
        'estado', 'forma_pago', 'banco_destino', 'tipo_cuenta_destino',
        'numero_cuenta_destino', 'titular_cuenta', 'fecha_pago',
        'referencia_pago', 'id_cuenta_banco_pago', 'id_conciliacion_ref',
        'ruta_pdf', 'nombre_pdf_original', 'hash_pdf', 'tamano_pdf',
        'creado_por', 'notas',
    ];

    protected $returnType    = 'array';
    // total_retenciones, anio y mes son GENERATED COLUMNS, no se setean.
    // useTimestamps=false para evitar el bug de CI4 con updatedField=null;
    // created_at y updated_at se manejan con DEFAULT CURRENT_TIMESTAMP del MySQL.
    protected $useTimestamps = false;
}
