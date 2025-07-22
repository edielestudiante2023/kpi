<?php

namespace App\Controllers;

use App\Models\IndicadorAuditoriaModel;
use App\Models\UserModel;
use CodeIgniter\Controller;

class AuditoriaController extends Controller
{
    protected $auditoriaModel;
    protected $userModel;

    public function __construct()
    {
        helper(['url', 'form', 'session']);
        $this->auditoriaModel = new IndicadorAuditoriaModel();
        $this->userModel      = new UserModel();
    }

    /**
     * Muestra el listado de auditorías de edición de indicadores
     */
    /* public function listAuditoria()
    {
        $auditorias = $this->auditoriaModel
            ->select(
                'indicador_auditoria.id_auditoria, 
                 indicador_auditoria.id_historial, 
                 indicador_auditoria.campo, 
                 indicador_auditoria.valor_anterior, 
                 indicador_auditoria.valor_nuevo, 
                 indicador_auditoria.fecha_edicion, 
                 users.nombre_completo AS editor_nombre'
            )
            ->join('users', 'users.id_users = indicador_auditoria.editor_id')
            ->orderBy('indicador_auditoria.fecha_edicion', 'DESC')
            ->findAll();

        return view('management/list_auditoria', [
            'auditorias' => $auditorias
        ]);
    } */
}
