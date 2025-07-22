<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\HistorialIndicadorModel;
use App\Models\IndicadorAuditoriaModel;
use App\Models\IndicadorPerfilModel;
use App\Models\PartesFormulaModel;
use App\Models\IndicadorModel;                  // ← Añadir
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class JefaturaController extends BaseController
{
    protected $userModel;
    protected $histModel;
    protected $auditModel;
    protected $ipModel;
    protected $partesModel;
    protected $indicadorModel;                    // ← Añadir

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'session', 'form']);

        $this->userModel      = new UserModel();
        $this->histModel      = new HistorialIndicadorModel();
        $this->auditModel     = new IndicadorAuditoriaModel();
        $this->ipModel        = new IndicadorPerfilModel();
        $this->partesModel    = new PartesFormulaModel();
        $this->indicadorModel = new IndicadorModel();   // ← Instanciar
    }


    /**
     * Dashboard de jefatura
     */
    public function jefaturadashboard()
    {
        $subordinados = $this->userModel->getSubordinadosDeJefe(session()->get('id_users'));

        return view('jefatura/jefaturadashboard', [
            'subordinados' => $subordinados,
        ]);
    }

    /**
     * Mis indicadores como jefe (solo para ver)
     */
    /**
     * Mis indicadores como jefe (solo para ver), 
     * ahora con partes de fórmula para renderizarla
     */
    public function misIndicadoresComoJefe()
    {
        $session = session();
        if (! $session->has('id_users') || ! $session->has('id_perfil_cargo')) {
            return redirect()->to('/login')
                ->with('error', 'Tu sesión ha expirado.');
        }

        $jefeId = $session->get('id_users');
        $perfil = $session->get('id_perfil_cargo');

        // 1) Indicadores asignados a este perfil
        $items   = $this->ipModel->getIndicadoresPorPerfil($perfil);

        $historial = $this->histModel
            ->select('id_indicador_perfil, cumple')
            ->where('id_usuario', $jefeId)
            ->orderBy('fecha_registro', 'DESC')
            ->findAll();

        // Mapear por perfil
        $cumpleMap = [];
        foreach ($historial as $h) {
            if (! isset($cumpleMap[$h['id_indicador_perfil']])) {
                $cumpleMap[$h['id_indicador_perfil']] = $h['cumple'];
            }
        }
        $periodo = date('Y-m-d');


        // 2) Precargar partes de fórmula para cada indicador
        $formulas = [];
        foreach ($items as $i) {
            $formulas[$i['id_indicador']] = $this->partesModel
                ->where('id_indicador', $i['id_indicador'])
                ->orderBy('orden', 'ASC')
                ->findAll();
        }

        // 3) Enviar todo a la vista
        return view('jefatura/misindicadorescomojefe', [
            'items'     => $items,
            'periodo'   => $periodo,
            'formulas'  => $formulas,
            'cumpleMap' => $cumpleMap, // ← ✅
        ]);
    }


    /**
     * Procesa el POST de "Mis Indicadores como Jefatura"
     */
    /**
     * Procesa el POST de "Mis Indicadores como Jefatura"
     * Ahora guardando también los datos de fórmula operacionalizada
     */
    public function saveIndicadoresComoJefe()
    {
        $session           = session();
        $jefeId            = $session->get('id_users');
        $periodoInput      = $this->request->getPost('periodo');
        $periodo = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodoInput))
            ? $periodoInput
            : date('Y-m-d');

        $resultados        = $this->request->getPost('resultado_real') ?? [];
        $comentarios       = $this->request->getPost('comentario')      ?? [];
        $formulasDigitadas = $this->request->getPost('formula_partes')  ?? [];

        // 🟣 Modo SINGLE
        if ($single = $this->request->getPost('single')) {
            $valor = trim($resultados[$single] ?? '');
            if ($valor !== '') {

                // 🔒 Validar duplicado antes del insert
                $existe = $this->histModel
                    ->where('id_usuario', $jefeId)
                    ->where('id_indicador_perfil', $single)
                    ->where('periodo', $periodo)
                    ->first();

                if ($existe) {
                    return redirect()->back()->with('error', 'Ya existe un resultado registrado para este indicador en esa fecha de corte.');
                }

                $relacion = $this->ipModel
                    ->select('indicadores.meta_valor, indicadores.tipo_meta, indicadores.id_indicador')
                    ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
                    ->where('id_indicador_perfil', $single)
                    ->first();

                $metaEsperada  = (float) $relacion['meta_valor'];
                $tipoMeta      = $relacion['tipo_meta'];
                $idIndicador   = $relacion['id_indicador'];
                $cumple        = null;
                $valorAnterior = null;

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
                            $anterior = $this->histModel
                                ->where('id_usuario', $jefeId)
                                ->where('id_indicador_perfil', $single)
                                ->orderBy('fecha_registro', 'DESC')
                                ->first();

                            if ($anterior && is_numeric($anterior['resultado_real'])) {
                                $valorAnterior = (float) $anterior['resultado_real'];
                                log_message('debug', "📊 Jefe IP {$single} | Usuario {$jefeId} | Valor anterior = {$valorAnterior} | Valor actual = {$valorNum}");

                                $this->indicadorModel
                                    ->where('id_indicador', $idIndicador)
                                    ->set('meta_valor', $valorAnterior)
                                    ->update();
                            } else {
                                $valorAnterior = $valorNum;
                                log_message('debug', "🆕 Primer comparativo jefe IP {$single} | Usuario {$jefeId} | Valor base = {$valorAnterior}");
                            }

                            $cumple = ($valorNum > $valorAnterior) ? 1 : 0;
                            break;
                    }
                }

                $json = ['valor' => $valor];
                if (isset($formulasDigitadas[$single])) {
                    $json['formula_partes'] = $formulasDigitadas[$single];
                }
                if ($tipoMeta === 'comparativa') {
                    $json['valor_anterior'] = $valorAnterior;
                }

                log_message('debug', '📝 Insertando (single) en historial_indicadores: ' . json_encode([
                    'id_indicador_perfil' => $single,
                    'id_usuario'          => $jefeId,
                    'resultado_real'      => $valor,
                    'valor_anterior'      => $valorAnterior,
                    'cumple'              => $cumple,
                ]));

                $this->histModel->insert([
                    'id_indicador_perfil' => $single,
                    'id_usuario'          => $jefeId,
                    'periodo'             => $periodo,
                    'valores_json'        => json_encode($json),
                    'resultado_real'      => $valor,
                    'comentario'          => trim($comentarios[$single] ?? ''),
                    'fecha_registro'      => date('Y-m-d H:i:s'),
                    'cumple'              => is_null($cumple) ? null : (int) $cumple,
                ]);
            }

            return redirect()->back()->with('success', 'Resultado guardado.');
        }

        // 🟣 Modo BATCH
        foreach ($resultados as $ipId => $valor) {
            $valor = trim($valor);
            if ($valor === '') {
                continue;
            }

            // 🔒 Validar duplicado antes del insert
            $existe = $this->histModel
                ->where('id_usuario', $jefeId)
                ->where('id_indicador_perfil', $ipId)
                ->where('periodo', $periodo)
                ->first();

            if ($existe) {
                continue; // Puedes acumular errores si prefieres
            }

            $relacion = $this->ipModel
                ->select('indicadores.meta_valor, indicadores.tipo_meta, indicadores.id_indicador')
                ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
                ->where('id_indicador_perfil', $ipId)
                ->first();

            $metaEsperada  = (float) $relacion['meta_valor'];
            $tipoMeta      = $relacion['tipo_meta'];
            $idIndicador   = $relacion['id_indicador'];
            $cumple        = null;
            $valorAnterior = null;

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
                        $anterior = $this->histModel
                            ->where('id_usuario', $jefeId)
                            ->where('id_indicador_perfil', $ipId)
                            ->orderBy('fecha_registro', 'DESC')
                            ->first();

                        if ($anterior && is_numeric($anterior['resultado_real'])) {
                            $valorAnterior = (float) $anterior['resultado_real'];
                            log_message('debug', "📊 Jefe IP {$ipId} | Usuario {$jefeId} | Valor anterior = {$valorAnterior} | Valor actual = {$valorNum}");

                            $this->indicadorModel
                                ->where('id_indicador', $idIndicador)
                                ->set('meta_valor', $valorAnterior)
                                ->update();
                        } else {
                            $valorAnterior = $valorNum;
                            log_message('debug', "🆕 Primer comparativo jefe IP {$ipId} | Usuario {$jefeId} | Valor base = {$valorAnterior}");
                        }

                        $cumple = ($valorNum > $valorAnterior) ? 1 : 0;
                        break;
                }
            }

            $json = ['valor' => $valor];
            if (isset($formulasDigitadas[$ipId])) {
                $json['formula_partes'] = $formulasDigitadas[$ipId];
            }
            if ($tipoMeta === 'comparativa') {
                $json['valor_anterior'] = $valorAnterior;
            }

            log_message('debug', '📝 Insertando (batch) en historial_indicadores: ' . json_encode([
                'id_indicador_perfil' => $ipId,
                'id_usuario'          => $jefeId,
                'resultado_real'      => $valor,
                'valor_anterior'      => $valorAnterior,
                'cumple'              => $cumple,
            ]));

            $this->histModel->insert([
                'id_indicador_perfil' => $ipId,
                'id_usuario'          => $jefeId,
                'periodo'             => $periodo,
                'valores_json'        => json_encode($json),
                'resultado_real'      => $valor,
                'comentario'          => trim($comentarios[$ipId] ?? ''),
                'fecha_registro'      => date('Y-m-d H:i:s'),
                'cumple'              => is_null($cumple) ? null : (int) $cumple,
            ]);
        }

        return redirect()->back()->with('success', 'Resultados guardados correctamente.');
    }






    /**
     * Edición rápida de indicadores del equipo
     */
    public function losIndicadoresDeMiEquipo()
    {
        // 1) Filtros de rango de fecha
        $fechaDesde    = $this->request->getGet('fecha_desde') ?? date('Y-m-01');
        $fechaHastaRaw = $this->request->getGet('fecha_hasta') ?? date('Y-m-d');
        $fechaHasta    = $fechaHastaRaw . ' 23:59:59';

        // 2) IDs de subordinados + el jefe
        $jefeId = session()->get('id_users');
        $subs   = $this->userModel->getSubordinadosDeJefe($jefeId);
        $subIds = array_column($subs, 'id_users');
        $subIds[] = $jefeId;

        // 3) Consulta de indicadores en el rango de fecha (ahora incluyendo periodo)
        $equipo = $this->histModel
            ->select([
                'historial_indicadores.id_historial',
                'historial_indicadores.periodo',
                'i.id_indicador       AS id_indicador',
                'usuarios.nombre_completo AS nombre_completo',
                'i.nombre             AS nombre_indicador',
                'i.meta_valor         AS meta_valor',
                'i.tipo_meta          AS tipo_meta',
                'i.metodo_calculo     AS metodo_calculo',
                'i.unidad             AS unidad',
                'historial_indicadores.resultado_real',
                'historial_indicadores.comentario',
            ])
            ->join('indicadores_perfil ip', 'ip.id_indicador_perfil = historial_indicadores.id_indicador_perfil')
            ->join('indicadores i',            'i.id_indicador = ip.id_indicador')
            ->join('users AS usuarios',        'usuarios.id_users = historial_indicadores.id_usuario')
            ->whereIn('historial_indicadores.id_usuario', $subIds)
            ->where('historial_indicadores.periodo >=', $fechaDesde)
            ->where('historial_indicadores.periodo <=', $fechaHasta)
            ->orderBy('historial_indicadores.periodo', 'DESC')
            ->orderBy('usuarios.nombre_completo', 'ASC')
            ->findAll();

        // 4) Precargar partes de fórmula
        $formulas = [];
        foreach ($equipo as $item) {
            $id = $item['id_indicador'];
            if (! isset($formulas[$id])) {
                $formulas[$id] = $this->partesModel
                    ->where('id_indicador', $id)
                    ->orderBy('orden', 'ASC')
                    ->findAll();
            }
        }

        // 5) Renderizar vista (pasamos fecha_hasta sin hora para el input)
        return view('jefatura/losindicadoresdemiequipo', [
            'equipo'      => $equipo,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHastaRaw,
            'formulas'    => $formulas,
        ]);
    }



    public function guardarIndicadoresDeEquipo()
    {
        $jefeId  = session()->get('id_users');
        $desde   = $this->request->getPost('periodo_desde') ?? date('Y-m', strtotime('-2 months'));
        $hasta   = $this->request->getPost('periodo_hasta') ?? date('Y-m');
        $cambios = $this->request->getPost('cambios') ?? [];

        foreach ($cambios as $idHistorial => $datos) {
            $old = $this->histModel->find($idHistorial);
            $new = [
                'resultado_real' => $datos['resultado_real'],
                'comentario'     => $datos['comentario'],
            ];
            $this->histModel->update($idHistorial, $new);

            foreach (['resultado_real', 'comentario'] as $campo) {
                if ((string)$old[$campo] !== (string)$new[$campo]) {
                    $this->auditModel->insert([
                        'id_historial'   => $idHistorial,
                        'editor_id'      => $jefeId,
                        'campo'          => $campo,
                        'valor_anterior' => $old[$campo],
                        'valor_nuevo'    => $new[$campo],
                    ]);
                }
            }
        }

        return redirect()
            ->to('/jefatura/losindicadoresdemiequipo?periodo_desde=' . $desde . '&periodo_hasta=' . $hasta)
            ->with('success', 'Indicadores del equipo actualizados correctamente.');
    }

    /**
     * Historial de mis indicadores (todos los periodos)
     */
    public function historialMisIndicadoresFeje()
    {
        $session    = session();
        $userId     = $session->get('id_users');
        $fechaDesde = $this->request->getGet('fecha_desde') ?? date('Y-m-01');
        $fechaHasta = $this->request->getGet('fecha_hasta') ?? date('Y-m-d');

        // 1) Traer el historial incluyendo id_indicador
        $historial = $this->histModel
            ->select([
                'historial_indicadores.*',
                'indicadores_perfil.id_indicador         AS id_indicador',
                'users.nombre_completo                   AS nombre_completo',
                'indicadores.nombre                      AS nombre_indicador',
                'indicadores.meta_valor                  AS meta_valor',         // ← FALTABA
                'indicadores.meta_descripcion            AS meta_texto',
                'indicadores.ponderacion                 AS ponderacion',
                'indicadores.periodicidad                AS periodicidad',
                'indicadores.tipo_meta',
                'indicadores.metodo_calculo',
                'indicadores.unidad',
                'indicadores.objetivo_proceso',
                'indicadores.objetivo_calidad',
                'indicadores.tipo_aplicacion',
                'indicadores.created_at                  AS creado_en',
                'historial_indicadores.resultado_real',
                'historial_indicadores.comentario',
                'historial_indicadores.valores_json',
                'historial_indicadores.fecha_registro',
            ])
            ->join(
                'indicadores_perfil',
                'indicadores_perfil.id_indicador_perfil = historial_indicadores.id_indicador_perfil'
            )
            ->join(
                'indicadores',
                'indicadores.id_indicador = indicadores_perfil.id_indicador'

            )
            ->join(
                'users',
                'users.id_users = historial_indicadores.id_usuario'
            )
            ->where('historial_indicadores.id_usuario', $userId)
            ->where('historial_indicadores.periodo >=', $fechaDesde)
            ->where('historial_indicadores.periodo <=', $fechaHasta)
            ->orderBy('historial_indicadores.periodo', 'DESC')
            ->findAll();

        // 2) Precargar las partes de fórmula indexadas por id_indicador
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

        // 3) Pasar todo a la vista
        return view('jefatura/historialmisindicadoresfeje', [
            'historial'    => $historial,
            'fecha_desde'  => $fechaDesde,
            'fecha_hasta'  => $fechaHasta,
            'formulasHist' => $formulasHist,     // ← importante
        ]);
    }


    /**

     */
    public function historialLosIndicadoresDeMiEquipo()
    {
        $session    = session();
        $jefeId     = $session->get('id_users');

        // 1) Leer filtros o poner valores por defecto
        $fechaDesde = $this->request->getGet('fecha_desde') ?? date('Y-m-01');
        $fechaHasta = $this->request->getGet('fecha_hasta') ?? date('Y-m-d');

        // 2) IDs de subordinados
        $subsIds = array_column(
            $this->userModel->getSubordinadosDeJefe($jefeId),
            'id_users'
        );
        if (empty($subsIds)) {
            $equipo = [];
        } else {
            // 3) Consulta con joins y filtro por rango de fecha
            $equipo = $this->histModel
                ->select([
                    'historial_indicadores.*',
                    'indicadores_perfil.id_indicador        AS id_indicador',
                    'users.nombre_completo                  AS nombre_completo',
                    'indicadores.nombre                     AS nombre_indicador',
                    'indicadores.meta_valor                 AS meta_valor',
                    'indicadores.meta_descripcion           AS meta_texto',
                    'indicadores.ponderacion                AS ponderacion',
                    'indicadores.periodicidad               AS periodicidad',
                    'indicadores.tipo_meta',
                    'indicadores.metodo_calculo',
                    'indicadores.unidad',
                    'indicadores.objetivo_proceso',
                    'indicadores.objetivo_calidad',
                    'indicadores.tipo_aplicacion',
                    'indicadores.created_at                 AS creado_en',
                    'historial_indicadores.resultado_real',
                    'historial_indicadores.comentario',
                    'historial_indicadores.valores_json',
                    'historial_indicadores.fecha_registro',
                    'historial_indicadores.periodo',
                    'historial_indicadores.cumple',
                ])

                ->join('indicadores_perfil', 'indicadores_perfil.id_indicador_perfil = historial_indicadores.id_indicador_perfil')
                ->join('indicadores',         'indicadores.id_indicador = indicadores_perfil.id_indicador')
                ->join('users',               'users.id_users = historial_indicadores.id_usuario')
                ->whereIn('historial_indicadores.id_usuario', $subsIds)
                ->where('historial_indicadores.periodo >=', $fechaDesde)
                ->where('historial_indicadores.periodo <=', $fechaHasta)
                ->orderBy('historial_indicadores.periodo', 'DESC')
                ->findAll();
        }

        // 4) Precargar partes de fórmula para cada indicador
        $formulasHist = [];
        foreach ($equipo as $r) {
            $id = $r['id_indicador'];
            if (! isset($formulasHist[$id])) {
                $formulasHist[$id] = $this->partesModel
                    ->where('id_indicador', $id)
                    ->orderBy('orden', 'ASC')
                    ->findAll();
            }
        }

        // 5) Renderizar vista con fórmulas incluidas
        return view('jefatura/historiallosindicadoresdemiequipo', [
            'equipo'       => $equipo,
            'fecha_desde'  => $fechaDesde,
            'fecha_hasta'  => $fechaHasta,
            'formulasHist' => $formulasHist,
        ]);
    }


    // Muestra el formulario para diligenciar la fórmula




    // Guarda en historial el resultado calculado
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
            : date('Y-m-d');

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
                        $valorAnterior = (float) $anterior['resultado_real'];
                        log_message('debug', "📊 Comparativa IP {$rel['id_indicador_perfil']} | Usuario {$userId} | Valor anterior = {$valorAnterior} | Valor actual = {$valorNum}");

                        // Actualizar meta_valor en la tabla indicadores
                        $this->indicadorModel
                            ->where('id_indicador', $idIndicador)
                            ->set('meta_valor', $valorAnterior)
                            ->update();
                        log_message('debug', "🔄 Indicador {$idIndicador} actualizado: meta_valor = {$valorAnterior}");
                    } else {
                        $valorAnterior = $valorNum;
                        log_message('debug', "🆕 Comparativa IP {$rel['id_indicador_perfil']} | Usuario {$userId} | Primer registro, se toma como valor base = {$valorAnterior}");
                    }

                    $cumple = ($valorNum > $valorAnterior) ? 1 : 0;
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

        return redirect()->to('jefatura/historialmisindicadoresfeje')->with('success', 'Resultado guardado correctamente.');
    }

    public function editarPeriodoEquipo()
    {
        if (! $this->request->isAJAX()) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Solicitud inválida.']);
        }

        $id    = $this->request->getPost('id_historial');
        $nuevo = $this->request->getPost('periodo');
        if (! $id || ! $nuevo) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Parámetros faltantes.']);
        }

        $old = $this->histModel->find($id);

        try {
            $this->histModel->update($id, ['periodo' => $nuevo]);

            // Auditoría solo si cambió realmente
            if ($old['periodo'] !== $nuevo) {
                $this->auditModel->insert([
                    'id_historial'   => $id,
                    'editor_id'      => session()->get('id_users'),
                    'campo'          => 'periodo',
                    'valor_anterior' => $old['periodo'],
                    'valor_nuevo'    => $nuevo,
                ]);
            }

            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Error al actualizar.']);
        }
    }

public function editarCumpleEquipo()
{
    if (! $this->request->isAJAX()) {
        log_message('debug', 'editarCumpleEquipo invocado sin AJAX.');
        return $this->response->setStatusCode(405)->setBody('Método no permitido');
    }

    $id         = $this->request->getPost('id_historial');
    $nuevoValor = $this->request->getPost('cumple');

    log_message('debug', "AJAX → recibidos id_historial={$id}, cumple='{$nuevoValor}'");

    // Validación de valor
    if (! in_array($nuevoValor, ['0', '1'], true)) {
        log_message('debug', "editarCumpleEquipo → valor no válido para cumple: '{$nuevoValor}'");
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Valor no válido para cumple.'
        ]);
    }

    // Preparar datos
    $data = ['cumple' => (int)$nuevoValor];
    log_message('debug', 'editarCumpleEquipo → a actualizar: ' . json_encode($data));

    // Intentar update y capturar resultado
    try {
        $result = $this->histModel->update($id, $data);
        log_message('debug', "editarCumpleEquipo → resultado de update(): " . var_export($result, true));

        // Leer de nuevo el registro para verificar
        $after = $this->histModel->find($id);
        log_message('debug', 'editarCumpleEquipo → valor en BD tras update: ' . json_encode($after['cumple']));

        return $this->response->setJSON([
            'success' => true,
            'cumple'  => (int)$after['cumple']
        ]);
    } catch (\Exception $e) {
        log_message('error', 'editarCumpleEquipo → excepción al actualizar: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error interno al actualizar.'
        ]);
    }
}


}
