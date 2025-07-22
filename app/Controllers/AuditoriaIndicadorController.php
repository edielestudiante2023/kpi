<?php

namespace App\Controllers;

use App\Models\AuditoriaIndicadorModel;
use CodeIgniter\Controller;

class AuditoriaIndicadorController extends Controller
{
    public function index()
    {
        $model = new AuditoriaIndicadorModel();

        $data['auditorias'] = $model->getAllAuditorias();

        return view('management/list_auditoria', $data);
    }
}
