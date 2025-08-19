<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\IndicadorPerfilModel;
use App\Models\HistorialIndicadorModel;
use App\Models\PartesFormulaModel;
use App\Models\IndicadorModel;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class TrabajadorController extends BaseController
{
    protected $userModel;
    protected $ipModel;
    protected $histModel;
    protected $partesModel;
    protected $indicadorModel;


    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'session', 'form']);
        $this->userModel = new UserModel();
        $this->ipModel   = new IndicadorPerfilModel();
        $this->histModel = new HistorialIndicadorModel();
        $this->partesModel = new PartesFormulaModel();
        $this->indicadorModel = new IndicadorModel();
    }

    /**
     * Dashboard inicial para trabajador
     */
    public function trabajadordashboard()
    {
        return view('trabajador/trabajadordashboard');
    }

    /**
     * Lista los indicadores asignados con campos extendidos del modelo Indicador
     */
    public function misIndicadores()
    {
        $session = session();
        if (! $session->has('id_users') || ! $session->has('id_perfil_cargo')) {
            $session->destroy();
            return redirect()->to('/login')
                ->with('error', 'Tu sesión ha expirado. Por favor vuelve a ingresar.');
        }

        $userId = $session->get('id_users');
        $perfil = $session->get('id_perfil_cargo');

        // 1) Obtener indicadores para el perfil
        $items   = $this->ipModel->getIndicadoresPorPerfil($perfil);
        $periodo = date('Y-m-d');

        // 2) Historial del periodo actual
        $history = $this->histModel
            ->where('id_usuario', $userId)
            ->where('periodo', $periodo)
            ->findAll();
        $histMap = [];
        foreach ($history as $h) {
            $histMap[$h['id_indicador_perfil']] = $h;
        }

        // 3) Cargar las partes de fórmula para cada indicador
        $formulas = [];
        foreach ($items as $item) {
            $formulas[$item['id_indicador']] = $this->partesModel
                ->where('id_indicador', $item['id_indicador'])
                ->orderBy('orden', 'ASC')
                ->findAll();
        }

        // 4) Enviar todo a la vista
        return view('trabajador/mis_indicadores', [
            'items'     => $items,
            'histMap'   => $histMap,
            'periodo'   => $periodo,
            'userId'    => $userId,
            'formulas'  => $formulas,
        ]);
    }


    /**
     * Guarda nuevos resultados de indicadores en historial
     */
    public function saveIndicadores()
    {
        $session     = session();
        $userId      = $session->get('id_users');
        $periodoInput = $this->request->getPost('periodo');
        $periodo = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodoInput))
            ? $periodoInput
            : date('Y-m-d');

        $resultados  = $this->request->getPost('resultado_real') ?? [];
        $comentarios = $this->request->getPost('comentario')     ?? [];
        $formulas    = $this->request->getPost('formula_partes') ?? [];

        foreach ($resultados as $ipId => $valor) {
            $valor = trim($valor);
            if ($valor === '') {
                continue;
            }

            // Validar duplicado de periodo
            $existe = $this->histModel
                ->where('id_usuario', $userId)
                ->where('id_indicador_perfil', $ipId)
                ->where('periodo', $periodo)
                ->first();

            if ($existe) {
                return redirect()->back()->with('error', 'Ya existe un resultado para ese indicador en esa fecha de corte.');
            }


            // 1) Traer meta y tipo de indicador
            $relacion = $this->ipModel
                ->select('indicadores.meta_valor, indicadores.tipo_meta, indicadores.id_indicador')
                ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
                ->where('id_indicador_perfil', $ipId)
                ->first();

            $metaEsperada   = (float) $relacion['meta_valor'];
            $tipoMeta       = $relacion['tipo_meta'];
            $idIndicador    = $relacion['id_indicador'];
            $cumple         = null;
            $valorAnterior  = null;

            log_message('debug', "🟡 Evaluando IP {$ipId} | Indicador {$idIndicador} | Usuario {$userId} | Tipo meta: {$tipoMeta} | Meta esperada: {$metaEsperada}");

            // 2) Evaluar cumplimiento
            if (is_numeric($valor)) {
                $valorNum = (float) $valor;

                switch ($tipoMeta) {
                    case 'mayor_igual':
                        $cumple = ($valorNum >= $metaEsperada) ? 1 : 0;
                        break;

                    case 'menor_igual':
                        $cumple = ($valorNum <= $metaEsperada) ? 1 : 0;
                        break;

                    case 'igual':
                        $cumple = ($valorNum == $metaEsperada) ? 1 : 0;
                        break;

                    case 'comparativa':
                        // Buscar último resultado anterior del mismo usuario e indicador/perfil
                        $anterior = $this->histModel
                            ->where('id_usuario', $userId)
                            ->where('id_indicador_perfil', $ipId)
                            ->orderBy('fecha_registro', 'DESC')
                            ->first();

                        if ($anterior && is_numeric($anterior['resultado_real'])) {
                            // Hay registro anterior válido - evaluar comparativa normalmente
                            $valorAnterior = (float) $anterior['resultado_real'];
                            log_message('debug', "📊 Comparativa IP {$ipId} | Usuario {$userId} | Valor anterior = {$valorAnterior} | Valor actual = {$valorNum}");
                            // Actualizar la meta_valor del indicador base solo si es tipo comparativa
                            $this->indicadorModel
                                ->where('id_indicador', $relacion['id_indicador'])
                                ->set('meta_valor', $valorAnterior)
                                ->update();

                            log_message('debug', "🔄 Indicador {$relacion['id_indicador']} actualizado: meta_valor = {$valorAnterior}");
                            $cumple = ($valorNum > $valorAnterior) ? 1 : 0;
                        } else {
                            // Primer registro - no hay base de comparación válida
                            $valorAnterior = $valorNum;
                            $cumple = null; // Marcar como "sin evaluar" en lugar de "no cumple"
                            log_message('debug', "🆕 Comparativa IP {$ipId} | Usuario {$userId} | Primer registro, valor base = {$valorAnterior} | Cumple = null (sin evaluar)");
                        }
                        break;
                }
            } else {
                log_message('debug', "⚠️ Valor no numérico para IP {$ipId}: " . print_r($valor, true));
            }

            // 3) JSON con partes de fórmula y valor anterior si aplica
            $json = ['valor' => $valor];
            if (isset($formulas[$ipId])) {
                $json['formula_partes'] = $formulas[$ipId];
            }
            if ($tipoMeta === 'comparativa') {
                $json['valor_anterior'] = $valorAnterior;
            }

            // 4) Registro en log antes de insertar
            log_message('debug', '📝 Insertando en historial_indicadores: ' . json_encode([
                'id_indicador_perfil' => $ipId,
                'id_usuario'          => $userId,
                'periodo'             => $periodo,
                'resultado_real'      => $valor,
                'valor_anterior'      => $valorAnterior,
                'cumple'              => $cumple,
            ]));


            // 5) Insertar en base de datos
            $this->histModel->insert([
                'id_indicador_perfil' => $ipId,
                'id_usuario'          => $userId,
                'periodo'             => $periodo,
                'valores_json'        => json_encode($json),
                'resultado_real'      => $valor,
                'comentario'          => trim($comentarios[$ipId] ?? ''),
                'fecha_registro'      => date('Y-m-d H:i:s'),
                'cumple'              => is_null($cumple) ? null : (int) $cumple,
            ]);
        }

        return redirect()->to('/trabajador/historialResultados')
            ->with('success', 'Resultado(s) guardado(s) correctamente.');
    }





    /**
     * Muestra historial de resultados con datos extendidos del indicador
     */
    public function historialResultados()
    {
        $session    = session();
        $userId     = $session->get('id_users');
        $fechaDesde = $this->request->getGet('fecha_desde') ?? date('Y-m-01');
        $fechaHasta = $this->request->getGet('fecha_hasta') ?? date('Y-m-d');

        // 1) Traer el historial

        $historial = $this->histModel
            ->select([
                'historial_indicadores.*',
                'historial_indicadores.cumple',
                'historial_indicadores.periodo',
                'indicadores_perfil.id_indicador        AS id_indicador',
                'indicadores.nombre                     AS nombre_indicador',
                'indicadores.meta_valor                 AS meta_valor',
                'indicadores.meta_descripcion           AS meta_texto',
                'indicadores.tipo_meta',
                'indicadores.metodo_calculo',
                'indicadores.unidad',
                'indicadores.objetivo_proceso',
                'indicadores.objetivo_calidad',
                'indicadores.tipo_aplicacion',
                'indicadores.created_at                 AS creado_en',
                'indicadores.periodicidad               AS periodicidad',
                'indicadores.ponderacion                AS ponderacion',


            ])
            ->join('indicadores_perfil', 'indicadores_perfil.id_indicador_perfil = historial_indicadores.id_indicador_perfil')
            ->join('indicadores',         'indicadores.id_indicador = indicadores_perfil.id_indicador')
            ->where('historial_indicadores.id_usuario', $userId)
            ->where('historial_indicadores.periodo >=', $fechaDesde . ' 00:00:00')
            ->where('historial_indicadores.periodo <=', $fechaHasta . ' 23:59:59')
            ->orderBy('historial_indicadores.periodo', 'DESC')
            ->findAll();

        // 2) Precargar partes de fórmula para cada indicador del historial
        $formulasHist = [];
        foreach ($historial as $r) {
            $id = $r['id_indicador'];
            if (! isset($formulasHist[$id])) {
                $formulasHist[$id] = $this->partesModel
                    ->where('id_indicador', $id)
                    ->orderBy('orden', 'ASC')
                    ->findAll();
            }
        }




        // 3) Enviar todo a la vista
        return view('trabajador/historial_resultados', [
            'historial'    => $historial,
            'fecha_desde'  => $fechaDesde,
            'fecha_hasta'  => $fechaHasta,
            'formulasHist' => $formulasHist,
        ]);
    }



    /**
     * Guarda en historial el resultado calculado por fórmula
     */
    public function guardarFormula($idIndicador)
    {
        $session = session();
        if (! $session->has('id_users') || ! $session->has('id_perfil_cargo')) {
            return redirect()->to('/login')->with('error', 'Tu sesión ha expirado.');
        }

        $userId    = $session->get('id_users');
        $perfil    = $session->get('id_perfil_cargo');
        $periodoInput = $this->request->getPost('periodo');
        $periodo = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodoInput))
            ? $periodoInput
            : date('Y-m-d'); // fallback en caso de error

        $resultado = $this->request->getPost('resultado');
        $partes    = $this->request->getPost('formula_partes') ?? [];

        log_message('debug', '🔍 ID usuario: ' . $userId);
        log_message('debug', '🔍 ID indicador: ' . $idIndicador);
        log_message('debug', '🔍 Resultado recibido: ' . $resultado);
        log_message('debug', '🔍 Periodo actual: ' . $periodo);

        // 1) Buscar relación perfil–indicador
        $rel = $this->ipModel
            ->select('indicadores_perfil.id_indicador_perfil, indicadores.meta_valor, indicadores.tipo_meta, indicadores.id_indicador')
            ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
            ->where('indicadores_perfil.id_perfil_cargo', $perfil)
            ->where('indicadores.id_indicador', $idIndicador)
            ->first();

        if (! $rel) {
            log_message('error', '❌ Indicador no asignado al perfil.');
            return redirect()->to('/trabajador/historial_resultados')->with('error', 'Indicador no asignado a tu perfil.');
        }

        // Validar duplicado de periodo
        $existe = $this->histModel
            ->where('id_usuario', $userId)
            ->where('id_indicador_perfil', $rel['id_indicador_perfil'])
            ->where('periodo', $periodo)
            ->first();

        if ($existe) {
            return redirect()->back()->with('error', 'Ya existe un resultado registrado para este indicador en esa fecha de corte.');
        }


        $metaEsperada   = (float) $rel['meta_valor'];
        $tipoMeta       = $rel['tipo_meta'];
        $idIndicador    = $rel['id_indicador'];
        $cumple         = null;
        $valorAnterior  = null;

        log_message('debug', 'ℹ️ Tipo meta: ' . $tipoMeta);
        log_message('debug', 'ℹ️ Meta esperada: ' . $metaEsperada);

        if (is_numeric($resultado)) {
            $valorNum = (float) $resultado;

            switch ($tipoMeta) {
                case 'mayor_igual':
                case 'fija':
                    $cumple = ($valorNum >= $metaEsperada) ? 1 : 0;
                    break;

                case 'menor_igual':
                    $cumple = ($valorNum <= $metaEsperada) ? 1 : 0;
                    break;

                case 'igual':
                    $cumple = ($valorNum == $metaEsperada) ? 1 : 0;
                    break;

                case 'comparativa':
                    // Buscar último resultado anterior (sin filtrar por periodo)
                    $anterior = $this->histModel
                        ->where('id_usuario', $userId)
                        ->where('id_indicador_perfil', $rel['id_indicador_perfil'])
                        ->orderBy('fecha_registro', 'DESC')
                        ->first();

                    if ($anterior && is_numeric($anterior['resultado_real'])) {
                        // Hay registro anterior válido - evaluar comparativa normalmente
                        $valorAnterior = (float) $anterior['resultado_real'];
                        log_message('debug', "📊 Comparativa IP {$rel['id_indicador_perfil']} | Usuario {$userId} | Valor anterior = {$valorAnterior} | Valor actual = {$valorNum}");

                        // Actualizar meta_valor en la tabla indicadores
                        $this->indicadorModel
                            ->where('id_indicador', $idIndicador)
                            ->set('meta_valor', $valorAnterior)
                            ->update();
                        log_message('debug', "🔄 Indicador {$idIndicador} actualizado: meta_valor = {$valorAnterior}");
                        $cumple = ($valorNum > $valorAnterior) ? 1 : 0;
                    } else {
                        // Primer registro - no hay base de comparación válida
                        $valorAnterior = $valorNum;
                        $cumple = null; // Marcar como "sin evaluar" en lugar de "no cumple"
                        log_message('debug', "🆕 Comparativa IP {$rel['id_indicador_perfil']} | Usuario {$userId} | Primer registro, valor base = {$valorAnterior} | Cumple = null (sin evaluar)");
                    }
                    break;
            }
        } else {
            log_message('debug', '⚠️ Resultado no numérico: ' . print_r($resultado, true));
        }

        // 3) JSON con valor anterior si aplica
        $json = [
            'valor'          => $resultado,
            'formula_partes' => $partes,
        ];
        if ($tipoMeta === 'comparativa') {
            $json['valor_anterior'] = $valorAnterior;
        }

        // 4) Registro en log antes de insertar
        log_message('debug', '📝 Insertando en historial_indicadores: ' . json_encode([
            'id_indicador_perfil' => $rel['id_indicador_perfil'],
            'id_usuario'          => $userId,
            'periodo'             => $periodo,
            'resultado_real'      => $resultado,
            'valor_anterior'      => $valorAnterior,
            'cumple'              => $cumple,
        ]));

        // 5) Insertar en base de datos
        $this->histModel->insert([
            'id_indicador_perfil' => $rel['id_indicador_perfil'],
            'id_usuario'          => $userId,
            'periodo'             => $periodo,
            'valores_json'        => json_encode($json),
            'resultado_real'      => $resultado,
            'comentario'          => null,
            'fecha_registro'      => date('Y-m-d H:i:s'),
            'cumple'              => is_null($cumple) ? null : (int) $cumple,
        ]);

        return redirect()->to('/trabajador/historial_resultados')->with('success', 'Resultado guardado correctamente.');
    }
}
