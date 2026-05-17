<?php

namespace App\Controllers;

use App\Models\MarketingLeadModel;
use App\Models\MarketingAccionModel;
use App\Models\MarketingTipoAccionModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class MarketingController extends BaseController
{
    protected $leadModel;
    protected $accionModel;
    protected $tipoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form', 'marketing']);
        $this->leadModel   = new MarketingLeadModel();
        $this->accionModel = new MarketingAccionModel();
        $this->tipoModel   = new MarketingTipoAccionModel();
    }

    private function chequearAcceso()
    {
        if (!marketing_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo Marketing.');
        }
        return null;
    }

    /**
     * Dashboard de marketing con los 5 KPIs clave.
     */
    public function dashboard()
    {
        if ($r = $this->chequearAcceso()) return $r;
        $db = \Config\Database::connect();

        // 1. Leads nuevos: esta semana vs semana pasada
        $inicioSemana = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $finSemana    = date('Y-m-d 23:59:59');
        $inicioSemanaAnt = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $finSemanaAnt    = date('Y-m-d 23:59:59', strtotime('sunday last week'));

        $leadsEstaSemana    = $this->leadModel->contarNuevosEnRango($inicioSemana, $finSemana);
        $leadsSemanaPasada  = $this->leadModel->contarNuevosEnRango($inicioSemanaAnt, $finSemanaAnt);

        // 2. Conteo por estado (donut)
        $porEstado = $this->leadModel->getConteoPorEstado();
        $totalLeads = array_sum($porEstado);

        // 3. Tasa de conversión lead → oportunidad
        $calificadosOConvertidos = $porEstado['calificado'];
        $tasaCalificacion = $totalLeads > 0 ? round($calificadosOConvertidos / $totalLeads * 100, 1) : 0;

        // 4. Leads por fuente (los que han convertido más)
        $porFuente = $db->query("
            SELECT
                COALESCE(f.nombre, 'Sin fuente') AS fuente_nombre,
                COUNT(l.id_lead) AS total,
                SUM(CASE WHEN l.estado = 'calificado' THEN 1 ELSE 0 END) AS calificados,
                ROUND(SUM(CASE WHEN l.estado = 'calificado' THEN 1 ELSE 0 END) / COUNT(l.id_lead) * 100, 1) AS tasa_calif
            FROM tbl_marketing_lead l
            LEFT JOIN tbl_crm_fuente f ON f.id_fuente = l.id_fuente
            GROUP BY f.id_fuente, f.nombre
            ORDER BY calificados DESC, total DESC
            LIMIT 10
        ")->getResultArray();

        // 5. Series de leads por semana (últimas 8)
        $seriesSemanas = [];
        for ($i = 7; $i >= 0; $i--) {
            $lunes = date('Y-m-d 00:00:00', strtotime("monday $i week ago"));
            $domingo = date('Y-m-d 23:59:59', strtotime("sunday $i week ago"));
            // Corregir orden (lunes < domingo en la misma semana)
            if (strtotime($lunes) > strtotime($domingo)) {
                $domingo = date('Y-m-d 23:59:59', strtotime($lunes . ' +6 days'));
            }
            $seriesSemanas[] = [
                'label' => 'Sem ' . date('d/m', strtotime($lunes)),
                'desde' => $lunes,
                'hasta' => $domingo,
                'cantidad' => $this->leadModel->contarNuevosEnRango($lunes, $domingo),
            ];
        }

        // 6. Acciones de marketing en el mes
        $inicioMes = date('Y-m-01');
        $finMes    = date('Y-m-t');
        $accionesMes = $this->accionModel->getResumenPorTipo($inicioMes, $finMes);
        $costoTotalMes = array_sum(array_map(fn($a) => (float) $a['costo_total'], $accionesMes));
        $totalAccionesMes = array_sum(array_map(fn($a) => (int) $a['cantidad'], $accionesMes));

        // 7. CAC informal: costo total mes / leads nuevos del mes
        $leadsMes = $this->leadModel->contarNuevosEnRango(
            date('Y-m-01 00:00:00'),
            date('Y-m-t 23:59:59')
        );
        $cacInformal = $leadsMes > 0 && $costoTotalMes > 0 ? round($costoTotalMes / $leadsMes, 0) : null;

        return view('marketing/dashboard', [
            'leadsEstaSemana'   => $leadsEstaSemana,
            'leadsSemanaPasada' => $leadsSemanaPasada,
            'porEstado'         => $porEstado,
            'totalLeads'        => $totalLeads,
            'tasaCalificacion'  => $tasaCalificacion,
            'porFuente'         => $porFuente,
            'seriesSemanas'     => $seriesSemanas,
            'accionesMes'       => $accionesMes,
            'totalAccionesMes'  => $totalAccionesMes,
            'costoTotalMes'     => $costoTotalMes,
            'cacInformal'       => $cacInformal,
            'leadsMes'          => $leadsMes,
        ]);
    }

    /**
     * Manual de usuario del módulo Marketing (vista estática paso a paso).
     */
    public function ayuda()
    {
        if ($r = $this->chequearAcceso()) return $r;
        return view('marketing/ayuda');
    }
}
