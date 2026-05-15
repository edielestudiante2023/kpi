<?php

namespace App\Controllers;

use App\Models\CrmOportunidadModel;
use App\Models\CrmInteraccionModel;
use App\Models\CrmEtapaModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CrmController extends BaseController
{
    protected $oportunidadModel;
    protected $interaccionModel;
    protected $etapaModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'crm']);
        $this->oportunidadModel = new CrmOportunidadModel();
        $this->interaccionModel = new CrmInteraccionModel();
        $this->etapaModel       = new CrmEtapaModel();
    }

    /**
     * Dashboard del CRM: KPIs + funnel + won/lost + ranking.
     */
    public function dashboard()
    {
        if (!crm_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo CRM.');
        }

        $idUsuario = (int) session()->get('id_users');
        $esAdmin = crm_es_admin();

        $metricas = $this->oportunidadModel->getMetricas($idUsuario, $esAdmin);
        $funnel   = $this->oportunidadModel->getFunnel($idUsuario, $esAdmin);
        $wonLost  = $this->oportunidadModel->getWonLostUltimosMeses($idUsuario, $esAdmin, 6);
        $ranking  = $this->oportunidadModel->getRankingResponsables($idUsuario, $esAdmin, 10);
        $tareasPendientes = $this->interaccionModel->getPendientesUsuario($idUsuario);

        return view('crm/dashboard', [
            'metricas'         => $metricas,
            'funnel'           => $funnel,
            'wonLost'          => $wonLost,
            'ranking'          => $ranking,
            'tareasPendientes' => $tareasPendientes,
            'esAdmin'          => $esAdmin,
        ]);
    }
}
