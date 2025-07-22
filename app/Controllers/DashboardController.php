<?php namespace App\Controllers;

use App\Models\IndicadorPerfilModel;
use App\Models\HistorialIndicadorModel;
use App\Models\PartesFormulaModel;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class DashboardController extends BaseController
{
    protected $ipModel;
    protected $histModel;
    protected $userModel;
    protected $partesModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form', 'session']);
        $this->ipModel   = new IndicadorPerfilModel();
        $this->histModel = new HistorialIndicadorModel();
        $this->userModel = new UserModel();
        $this->partesModel = new PartesFormulaModel();

    }



    public function misIndicadores()
{
    $session = session();
    $userId  = $session->get('id_users');
    $user    = $this->userModel->find($userId);
    $perfil  = $user['id_perfil_cargo'];

    // Obtener asignaciones de indicadores para el perfil
    $items = $this->ipModel
    ->select('
        indicadores_perfil.id_indicador_perfil,
        indicadores_perfil.id_indicador,
        indicadores.nombre,
        indicadores.periodicidad,
        indicadores.meta_valor,
        indicadores.meta_descripcion,
        indicadores.ponderacion,
        indicadores.unidad,
        indicadores.tipo_meta,
        indicadores.metodo_calculo,
        indicadores.objetivo_proceso,
        indicadores.objetivo_calidad,
        indicadores.tipo_aplicacion,
        indicadores.activo,
        indicadores.created_at
    ')
    ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
    ->where('id_perfil_cargo', $perfil)
    ->findAll();


    // Periodo actual (YYYY-MM)
    $periodo = date('Y-m');
    $history = $this->histModel
        ->where('id_usuario', $userId)
        ->where('periodo', $periodo)
        ->findAll();

    // Map historial por indicador_perfil
    $histMap = [];
    foreach ($history as $h) {
        $histMap[$h['id_indicador_perfil']] = $h;
    }

    // Mapear fórmulas por indicador
    $formulas = [];
    foreach ($items as $item) {
        $idIndicador = $item['id_indicador'];
        $formulas[$idIndicador] = $this->partesModel
            ->where('id_indicador', $idIndicador)
            ->orderBy('orden', 'ASC')
            ->findAll();
    }

    return view('trabajador/mis_indicadores', [
        'items'    => $items,
        'histMap'  => $histMap,
        'periodo'  => $periodo,
        'formulas' => $formulas
    ]);
}


    // Guardar resultados del trabajador
    public function saveIndicadores()
    {
        $session = session();
        $userId  = $session->get('id_users');
        $periodo = date('Y-m');
        $post    = $this->request->getPost();

        foreach ($post['resultado_real'] as $ipId => $valor) {
            $dato = [
                'id_indicador_perfil' => $ipId,
                'id_usuario'          => $userId,
                'periodo'             => $periodo,
                'valores_json'        => json_encode(['resultado' => $valor]),
                'resultado_real'      => $valor,
                'comentario'          => $post['comentario'][$ipId] ?? null
            ];

            // Actualizar o insertar
            $exist = $this->histModel
                ->where('id_indicador_perfil', $ipId)
                ->where('id_usuario', $userId)
                ->where('periodo', $periodo)
                ->first();

            if ($exist) {
                $this->histModel->update($exist['id_historial'], $dato);
            } else {
                $this->histModel->insert($dato);
            }
        }

        return redirect()->back()->with('success', 'Resultados guardados.');
    }
}
