<?php

namespace App\Controllers;

use App\Models\EquipoModel;
use App\Models\UserModel;
use App\Models\HistorialIndicadorModel;
use App\Models\IndicadorModel;
use App\Models\IndicadorPerfilModel;
use App\Models\PartesFormulaModel;

use CodeIgniter\Controller;

class JerarquiaController extends Controller
{
    protected $equipoModel;
    protected $userModel;
    protected $histModel;
    protected $indicadorModel;
    protected $ipModel;
    protected $partesModel;

    public function __construct()
    {
        helper(['url', 'session', 'form']);
        $this->equipoModel    = new EquipoModel();
        $this->userModel       = new UserModel();
        $this->histModel       = new HistorialIndicadorModel();
        $this->indicadorModel  = new IndicadorModel();
        $this->ipModel         = new IndicadorPerfilModel();
        $this->partesModel     = new PartesFormulaModel();
    }

    public function historialIndicadoresJerarquicos()
    {
        $jefeId = session()->get('id_users');
        $fechaDesde = $this->request->getGet('fecha_desde') ?? date('Y-m-01');
        $fechaHasta = $this->request->getGet('fecha_hasta') ?? date('Y-m-d');

        // 1. Subordinados recursivos
        $subIds = $this->obtenerSubordinadosJerarquicos($jefeId);





        // 2. Consulta principal
        $equipo = $this->histModel
            ->select([
                'historial_indicadores.*',
                'historial_indicadores.id_historial AS id_historial',
                'indicadores_perfil.id_indicador AS id_indicador',
                'users.nombre_completo AS nombre_completo',
                'indicadores.nombre AS nombre_indicador',
                'indicadores.meta_valor',
                'indicadores.meta_descripcion',
                'indicadores.ponderacion',
                'indicadores.periodicidad',
                'indicadores.tipo_meta',
                'indicadores.metodo_calculo',
                'indicadores.unidad',
                'indicadores.objetivo_proceso',
                'indicadores.objetivo_calidad',
                'indicadores.tipo_aplicacion',
                'indicadores.created_at AS creado_en',
            ])
            ->join('indicadores_perfil', 'indicadores_perfil.id_indicador_perfil = historial_indicadores.id_indicador_perfil')
            ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
            ->join('users', 'users.id_users = historial_indicadores.id_usuario')
            ->whereIn('historial_indicadores.id_usuario', $subIds)
            ->where('historial_indicadores.periodo >=', $fechaDesde)
            ->where('historial_indicadores.periodo <=', $fechaHasta)
            ->orderBy('historial_indicadores.periodo', 'DESC')
            ->findAll();


        // 🔍 👉 Pega aquí el var_dump para depuración:


        // 3. Precargar fórmulas por indicador
        $formulasHist = [];
        foreach ($equipo as $row) {
            $id = $row['id_indicador'];
            if (!isset($formulasHist[$id])) {
                $formulasHist[$id] = $this->partesModel
                    ->where('id_indicador', $id)
                    ->orderBy('orden', 'ASC')
                    ->findAll();
            }
        }

        return view('jerarquia/historialjerarquico', [
            'equipo'       => $equipo,
            'fecha_desde'  => $fechaDesde,
            'fecha_hasta'  => $fechaHasta,
            'formulasHist' => $formulasHist,
        ]);
    }

    /**
     * Retorna todos los ID de subordinados de forma recursiva a partir del jefe actual.
     */
    private function obtenerSubordinadosJerarquicos($jefeId)
    {
        $todos = [];
        $pendientes = [$jefeId];

        while (!empty($pendientes)) {
            $actual = array_pop($pendientes);
            $result = $this->equipoModel
                ->where('id_jefe', $actual)
                ->where('estado_relacion', 'activo')
                ->findAll();

            foreach ($result as $r) {
                $idSub = (int) $r['id_subordinado'];
                if (!in_array($idSub, $todos)) {
                    $todos[] = $idSub;
                    $pendientes[] = $idSub;
                }
            }
        }

        return $todos;
    }

    public function verEquipoExtendido()
    {
        $jefeId = session()->get('id_users');
        $subIds = $this->obtenerSubordinadosJerarquicos($jefeId);

        $equipoExtendido = $this->userModel
            ->select('id_users, nombre_completo, correo, cargo')
            ->whereIn('id_users', $subIds)
            ->findAll();

        return view('jerarquia/equipoextendido', [
            'equipo' => $equipoExtendido
        ]);
    }
}
