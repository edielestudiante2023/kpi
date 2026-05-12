<?php

namespace App\Controllers;

use App\Models\IaConversacionModel;
use App\Models\IaMensajeModel;
use App\Models\BalanceSnapshotModel;
use App\Services\AnthropicService;
use App\Services\FinancialToolsService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AsesoriaIaController extends BaseController
{
    protected $convModel;
    protected $msgModel;
    protected $snapModel;
    protected $rolesPermitidos = [1, 2, 3]; // superadmin, admin, jefatura

    /** System prompts y modelo por preset */
    private const PRESETS = [
        'diagnostico' => [
            'titulo' => '🩺 Diagnóstico de salud financiera',
            'descripcion' => 'Resumen ejecutivo del estado actual + alertas críticas',
            'system' => "Eres un CFO virtual senior asesorando a la gerencia de **Cycloid Talent**, una empresa de servicios de Salud y Seguridad en el Trabajo (SST) y Reclutamiento de Personal (RPS) en Colombia. Tu rol es entregar un diagnóstico ejecutivo de la salud financiera de la empresa al día de hoy.\n\nINSTRUCCIONES:\n1. Llama primero a `obtener_balance_al_corte` para conocer la posición consolidada actual.\n2. Si detectas un estado negativo o cartera elevada, llama a tools adicionales para profundizar (`obtener_deudas_activas`, `obtener_cartera_detalle`).\n3. Entrega un diagnóstico estructurado en español ejecutivo, máximo 400 palabras:\n   - **Veredicto** (1 línea: SANA / EN ALERTA / CRÍTICA)\n   - **Top 3 indicadores clave** con valor y comentario corto\n   - **2-3 alertas** prioritarias si las hay\n   - **Próximo paso recomendado** (acción concreta)\n4. Usa formato Markdown con headers ##, bullets y negritas. Montos siempre en pesos colombianos con separador de miles (ej: $1.234.567).",
            'usuario_inicial' => 'Realiza el diagnóstico de salud financiera de Cycloid Talent al día de hoy. Incluye veredicto, indicadores clave, alertas y próximo paso recomendado.',
        ],
        'analisis_cierre' => [
            'titulo' => '📊 Análisis del último cierre mensual',
            'descripcion' => 'Análisis profundo del snapshot más reciente',
            'system' => "Eres un analista financiero senior revisando el cierre mensual de **Cycloid Talent**. Tu trabajo es interpretar el snapshot del mes y compararlo con meses anteriores para identificar patrones.\n\nINSTRUCCIONES:\n1. Llama a `obtener_balance_al_corte` con la fecha indicada para ver el snapshot.\n2. Llama a `obtener_facturado_recaudo_por_mes` para SST y para RPS para ver tendencias del año.\n3. Si hay cartera relevante, profundiza con `obtener_cartera_detalle`.\n4. Entrega un análisis en español ejecutivo (~500 palabras) con:\n   - **Hechos del mes** (qué pasó con cartera, bancos, deudas)\n   - **Comparativo vs meses anteriores** (qué cambió, tendencia)\n   - **Cumplimiento de presupuesto** del año hasta el mes\n   - **Recomendaciones** específicas para el siguiente mes\nFormato Markdown. Pesos colombianos con separador de miles. Sé concreto, evita generalidades.",
            'usuario_inicial' => null, // se rellena dinámicamente con fecha del snapshot
        ],
        'comparativo' => [
            'titulo' => '📈 Comparativo de períodos',
            'descripcion' => 'Compara dos snapshots o períodos y resalta tendencias',
            'system' => "Eres un analista comparativo de **Cycloid Talent**. Tu objetivo es comparar dos períodos y entregar un análisis de variaciones, tendencias y patrones.\n\nINSTRUCCIONES:\n1. Obtén balances de las dos fechas con `obtener_balance_al_corte`.\n2. Calcula variaciones absolutas y porcentuales en cada KPI.\n3. Usa `obtener_facturado_recaudo_por_mes` si necesitas ver la curva mensual entre los dos cortes.\n4. Entrega tabla comparativa + análisis textual (~400 palabras):\n   - **Tabla comparativa** (markdown) con variaciones\n   - **Lectura ejecutiva** (qué mejoró, qué empeoró)\n   - **Conclusión** y posible causa de las variaciones más significativas\nFormato Markdown. Pesos colombianos con separador de miles.",
            'usuario_inicial' => null,
        ],
        'cartera' => [
            'titulo' => '💰 Priorización de gestión de cobro',
            'descripcion' => 'Identifica facturas a priorizar por monto × antigüedad',
            'system' => "Eres un asesor de cobranzas senior para **Cycloid Talent**. Tu objetivo es priorizar la gestión de cobro identificando facturas estratégicas a perseguir.\n\nINSTRUCCIONES:\n1. Llama a `obtener_cartera_detalle` para SST y RPS (límite 50 cada uno).\n2. Identifica facturas a priorizar según: (a) días de mora, (b) monto del saldo, (c) cliente recurrente.\n3. Entrega (~350 palabras):\n   - **Top 5 facturas críticas** (tabla markdown con cliente, días mora, saldo, prioridad)\n   - **Clientes con cartera concentrada** (si algún cliente tiene >20% del total)\n   - **Estrategia de cobro recomendada** (cómo abordar cada grupo)\n   - **Monto recuperable estimado** si se persiguen las top 5\nFormato Markdown. Pesos colombianos con separador de miles.",
            'usuario_inicial' => 'Analiza la cartera pendiente de SST y RPS, identifica las facturas que debemos priorizar para cobro y dame una estrategia concreta.',
        ],
        'estrategia' => [
            'titulo' => '🎯 Recomendaciones estratégicas',
            'descripcion' => 'Análisis profundo + roadmap accionable (modelo premium)',
            'system' => "Eres un consultor estratégico senior para **Cycloid Talent**. La gerente te pide recomendaciones de alto nivel para mejorar la salud financiera y alcanzar los presupuestos anuales. Tienes acceso a TODOS los datos del sistema.\n\nINSTRUCCIONES:\n1. Empieza con un panorama completo: balance actual (`obtener_balance_al_corte`), tendencias de facturación y recaudo por portafolio (`obtener_facturado_recaudo_por_mes` para SST, RPS y FRAMEWORK), deudas (`obtener_deudas_activas`) y muestreo de cartera (`obtener_cartera_detalle`).\n2. Identifica los 3 problemas/oportunidades más significativos basándote en evidencia numérica.\n3. Para cada uno propón una recomendación concreta con: acción, plazo (corto/medio plazo), impacto esperado en pesos, riesgo asumido.\n4. Entrega (~700 palabras) con esta estructura:\n   - **Resumen ejecutivo** (3-4 líneas con el panorama)\n   - **Análisis de cumplimiento de presupuesto** del año\n   - **3 recomendaciones estratégicas accionables**, cada una con: descripción, plazo, impacto estimado en COP, riesgos\n   - **Roadmap propuesto** para los próximos 3 meses\n   - **KPIs a vigilar** semana a semana\nFormato Markdown, tono ejecutivo CFO. Pesos colombianos con separador de miles.",
            'usuario_inicial' => 'Quiero recomendaciones estratégicas para mejorar la salud financiera de Cycloid Talent y alcanzar el presupuesto anual. Analiza la situación completa y dame un roadmap de los próximos 3 meses.',
        ],
    ];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->convModel = new IaConversacionModel();
        $this->msgModel  = new IaMensajeModel();
        $this->snapModel = new BalanceSnapshotModel();
    }

    /**
     * Verifica que el usuario tenga rol permitido.
     * Retorna response de redirect si no, null si sí.
     */
    private function checkRol()
    {
        $rolId = (int) (session()->get('rol_id') ?? 0);
        if (! in_array($rolId, $this->rolesPermitidos, true)) {
            return redirect()->to('/conciliaciones/dashboard')
                ->with('errors', ['No tienes permisos para acceder al módulo de Asesoría IA. Solicita acceso a un administrador.']);
        }
        return null;
    }

    /**
     * Vista principal: presets + historial + consumo del mes
     */
    public function index()
    {
        if ($r = $this->checkRol()) return $r;

        $data['presets']     = self::PRESETS;
        $data['historial']   = $this->convModel
            ->orderBy('created_at', 'DESC')
            ->findAll(20);
        $data['costoMes']    = $this->msgModel->costoMesActual();
        $data['budgetMes']   = (float) env('IA_BUDGET_MES_USD', 5.0);
        $data['porcentaje']  = $data['budgetMes'] > 0
            ? min(100, round($data['costoMes'] / $data['budgetMes'] * 100, 1))
            : 0;

        $data['snapshots']   = $this->snapModel
            ->select('id_snapshot, fecha_corte')
            ->orderBy('fecha_corte', 'DESC')
            ->findAll(12);

        return view('conciliaciones/asesoria_ia', $data);
    }

    /**
     * POST: dispara un preset y crea la conversación
     */
    public function analizar()
    {
        if ($r = $this->checkRol()) return $r;

        $preset = $this->request->getPost('preset');
        if (! isset(self::PRESETS[$preset])) {
            return redirect()->back()->with('errors', ['Preset inválido.']);
        }

        // ── Check de budget ──
        $costoActual = $this->msgModel->costoMesActual();
        $budget = (float) env('IA_BUDGET_MES_USD', 5.0);
        if ($costoActual >= $budget) {
            return redirect()->back()->with('errors', [
                sprintf("Límite mensual alcanzado ($%.2f / $%.2f USD). El módulo se reactivará el próximo mes.",
                    $costoActual, $budget)
            ]);
        }

        $cfg = self::PRESETS[$preset];

        // Mensaje inicial del usuario (puede venir con parámetros)
        $userMessage = $cfg['usuario_inicial'];
        $idSnapshotRef = null;

        if ($preset === 'analisis_cierre') {
            $idSnap = (int) $this->request->getPost('id_snapshot');
            $snap = $idSnap ? $this->snapModel->find($idSnap) : null;
            if (! $snap) {
                return redirect()->back()->with('errors', ['Selecciona un snapshot para analizar.']);
            }
            $idSnapshotRef = $idSnap;
            $userMessage = "Analiza en detalle el cierre del {$snap['fecha_corte']} de Cycloid Talent. Llama a la tool obtener_balance_al_corte con esa fecha exacta y compara contra la tendencia del año.";
        }

        if ($preset === 'comparativo') {
            $fecha1 = $this->request->getPost('fecha_a');
            $fecha2 = $this->request->getPost('fecha_b');
            if (! $fecha1 || ! $fecha2) {
                return redirect()->back()->with('errors', ['Indica las dos fechas a comparar.']);
            }
            $userMessage = "Compara la posición financiera de Cycloid Talent entre las fechas {$fecha1} y {$fecha2}. Llama a obtener_balance_al_corte para ambas, calcula variaciones y entrega una tabla comparativa con conclusión ejecutiva.";
        }

        // ── Llamada a Claude ──
        try {
            $ant = new AnthropicService();
            $fts = new FinancialToolsService();
            $tools = $fts->definiciones();

            $resultado = $ant->analizar(
                $cfg['system'],
                $tools,
                [['role' => 'user', 'content' => $userMessage]],
                fn(string $name, array $input) => $fts->ejecutar($name, $input),
                6
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('errors', ['Error IA: ' . $e->getMessage()]);
        }

        // Guardar conversación + mensajes
        $usuario = session()->get('nombre_completo') ?? session()->get('email') ?? 'sistema';
        $idConv = $this->convModel->insert([
            'titulo'          => $cfg['titulo'] . ' — ' . date('d/m/Y H:i'),
            'tipo'            => $preset,
            'id_snapshot_ref' => $idSnapshotRef,
            'creado_por'      => $usuario,
        ], true);

        // Mensaje del usuario
        $this->msgModel->insert([
            'id_conversacion' => $idConv,
            'rol'             => 'user',
            'contenido'       => $userMessage,
        ]);

        // Mensaje del assistant (texto final + meta)
        $this->msgModel->insert([
            'id_conversacion'   => $idConv,
            'rol'               => 'assistant',
            'contenido'         => $resultado['final_text'],
            'tool_calls'        => json_encode($this->extraerToolCalls($resultado['messages_acumulados']), JSON_UNESCAPED_UNICODE),
            'tokens_input'      => $resultado['tokens_input_total'],
            'tokens_output'     => $resultado['tokens_output_total'],
            'tokens_cache_read' => $resultado['tokens_cache_read_total'],
            'tokens_cache_write'=> $resultado['tokens_cache_write_total'],
            'modelo'            => $ant->modelo(),
            'costo_usd'         => round($resultado['costo_total_usd'], 6),
        ]);

        return redirect()->to("/conciliaciones/asesoria-ia/ver/{$idConv}");
    }

    /**
     * Ver el detalle de una conversación
     */
    public function ver($id)
    {
        if ($r = $this->checkRol()) return $r;

        $idConv = (int) $id;
        $data['conversacion'] = $this->convModel->find($idConv);
        if (! $data['conversacion']) {
            return redirect()->to('/conciliaciones/asesoria-ia')->with('errors', ['Conversación no encontrada.']);
        }
        $data['mensajes'] = $this->msgModel
            ->where('id_conversacion', $idConv)
            ->orderBy('created_at', 'ASC')->findAll();
        $data['costoMes']  = $this->msgModel->costoMesActual();
        $data['budgetMes'] = (float) env('IA_BUDGET_MES_USD', 5.0);

        return view('conciliaciones/asesoria_ia_conversacion', $data);
    }

    /**
     * Eliminar conversación
     */
    public function eliminar($id)
    {
        if ($r = $this->checkRol()) return $r;
        $this->convModel->delete((int) $id); // borra mensajes por FK CASCADE
        return redirect()->to('/conciliaciones/asesoria-ia')
            ->with('success', 'Conversación eliminada.');
    }

    /**
     * Extrae las tool calls de la lista de mensajes acumulados de Claude
     * para guardarlas como JSON en tbl_ia_mensaje.tool_calls
     */
    private function extraerToolCalls(array $messages): array
    {
        $calls = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? '') !== 'assistant') continue;
            $content = is_array($m['content'] ?? null) ? $m['content'] : [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') === 'tool_use') {
                    $calls[] = [
                        'name'  => $block['name'] ?? '',
                        'input' => $block['input'] ?? [],
                    ];
                }
            }
        }
        return $calls;
    }
}
