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

    /** Identidad base de OTTO — se prepende a todos los system prompts */
    private const OTTO_IDENTITY = "Eres **OTTO**, el asesor financiero IA de **Cycloid Talent** (empresa colombiana de servicios SST y reclutamiento RPS). Hablas en español Colombia con tono ejecutivo, claro y directo. Te refieres a montos en pesos colombianos con separador de miles (\$1.234.567). Tienes acceso a los datos reales del sistema vía herramientas que debes usar antes de responder con números. NUNCA inventes cifras: si no podés obtenerlas con tus tools, dilo explícitamente. Sé conciso pero accionable.\n\n" .
        "HERRAMIENTAS DISPONIBLES (úsalas cuando apliquen):\n" .
        "• `obtener_balance_al_corte(fecha)` — estado consolidado a una fecha\n" .
        "• `obtener_facturado_recaudo_por_mes(portafolio, anio)` — series mensuales\n" .
        "• `obtener_cartera_detalle(portafolio)` — facturas pendientes\n" .
        "• `obtener_deudas_activas()` — deudas con DIAN/otros\n" .
        "• `buscar_cliente(q)` — busca cliente por NIT o nombre parcial\n" .
        "• `obtener_actividad_cliente(nit)` — historial completo de UN cliente\n" .
        "• `obtener_facturas_pagadas(portafolio?, anio?, mes?)` — últimas pagadas\n" .
        "• `consultar_factura(comprobante)` — detalle de UNA factura puntual\n" .
        "• `buscar_movimiento_bancario(texto, desde?, hasta?)` — busca en descripción/transacción/referencia\n" .
        "• `obtener_top_clientes(portafolio?, criterio=facturado|cartera_pendiente)` — ranking\n" .
        "• `obtener_actividad_mes(anio, mes, portafolio?)` — resumen mensual con top ingresos/egresos\n" .
        "• `obtener_cuentas_cobro(estado?, centro_costo?)` — cuentas de cobro de contratistas externos\n\n";

    /** Prompt del modo conversacional libre del widget */
    private const SYSTEM_LIBRE = self::OTTO_IDENTITY .
        "Estás en modo conversacional dentro del widget de chat. Reglas:\n" .
        "- Antes de dar números, llama a las tools relevantes\n" .
        "- Si una pregunta menciona un cliente por nombre, usa primero `buscar_cliente` para obtener el NIT exacto, luego `obtener_actividad_cliente`\n" .
        "- Si te preguntan por una factura específica, usa `consultar_factura`\n" .
        "- Si te preguntan por un movimiento de banco/pago, usa `buscar_movimiento_bancario`\n" .
        "- Para responder usa Markdown corto (negritas, listas, tablas pequeñas)\n" .
        "- Responde en máximo 250 palabras salvo que la pregunta exija más detalle\n" .
        "- Si la pregunta es ambigua, hacé 1 sola contra-pregunta clarificadora\n" .
        "- Si la pregunta no tiene que ver con finanzas de Cycloid, redirige amablemente";

    /** System prompts y modelo por preset */
    private const PRESETS = [
        'diagnostico' => [
            'titulo' => '🩺 Diagnóstico de salud financiera',
            'descripcion' => 'Resumen ejecutivo del estado actual + alertas críticas',
            'system' => self::OTTO_IDENTITY . "Tu tarea ahora es entregar un **diagnóstico ejecutivo de salud financiera**.\n\nPROCEDIMIENTO:\n1. Llama primero a `obtener_balance_al_corte` para conocer la posición consolidada actual.\n2. Si detectas estado negativo o cartera elevada, profundiza con `obtener_deudas_activas` y `obtener_cartera_detalle`.\n3. Entrega un diagnóstico estructurado (máximo 400 palabras):\n   - **Veredicto** (1 línea: SANA / EN ALERTA / CRÍTICA)\n   - **Top 3 indicadores clave** con valor y comentario corto\n   - **2-3 alertas** prioritarias si las hay\n   - **Próximo paso recomendado** (acción concreta)\nFormato Markdown con headers, bullets y negritas.",
            'usuario_inicial' => 'Realiza el diagnóstico de salud financiera de Cycloid Talent al día de hoy. Incluye veredicto, indicadores clave, alertas y próximo paso recomendado.',
        ],
        'analisis_cierre' => [
            'titulo' => '📊 Análisis del último cierre mensual',
            'descripcion' => 'Análisis profundo del snapshot más reciente',
            'system' => self::OTTO_IDENTITY . "Tu tarea es interpretar un cierre mensual congelado y compararlo con la tendencia del año.\n\nPROCEDIMIENTO:\n1. Llama a `obtener_balance_al_corte` con la fecha exacta del snapshot.\n2. Llama a `obtener_facturado_recaudo_por_mes` para SST y para RPS.\n3. Si hay cartera relevante, profundiza con `obtener_cartera_detalle`.\n4. Entrega análisis (~500 palabras):\n   - **Hechos del mes**\n   - **Comparativo vs meses anteriores**\n   - **Cumplimiento de presupuesto del año hasta el mes**\n   - **Recomendaciones específicas para el siguiente mes**\nFormato Markdown. Sé concreto, evita generalidades.",
            'usuario_inicial' => null,
        ],
        'comparativo' => [
            'titulo' => '📈 Comparativo de períodos',
            'descripcion' => 'Compara dos snapshots o períodos y resalta tendencias',
            'system' => self::OTTO_IDENTITY . "Tu tarea es comparar dos períodos y resaltar variaciones, tendencias y posibles causas.\n\nPROCEDIMIENTO:\n1. Obtén balances de ambas fechas con `obtener_balance_al_corte`.\n2. Calcula variaciones absolutas y porcentuales.\n3. Si necesitás el detalle mensual entre ambas, usa `obtener_facturado_recaudo_por_mes`.\n4. Entrega (~400 palabras):\n   - **Tabla comparativa Markdown** con variaciones\n   - **Lectura ejecutiva** (qué mejoró, qué empeoró)\n   - **Conclusión** y posible causa de variaciones más significativas",
            'usuario_inicial' => null,
        ],
        'cartera' => [
            'titulo' => '💰 Priorización de gestión de cobro',
            'descripcion' => 'Identifica facturas a priorizar por monto × antigüedad',
            'system' => self::OTTO_IDENTITY . "Tu tarea es priorizar la gestión de cobro identificando facturas estratégicas.\n\nPROCEDIMIENTO:\n1. Llama a `obtener_cartera_detalle` para SST y RPS (límite 50 cada uno).\n2. Identifica facturas a priorizar según: (a) días de mora, (b) monto del saldo, (c) cliente recurrente.\n3. Entrega (~350 palabras):\n   - **Top 5 facturas críticas** (tabla con cliente, días mora, saldo, prioridad)\n   - **Clientes con cartera concentrada** (si algún cliente tiene >20% del total)\n   - **Estrategia de cobro** (cómo abordar cada grupo)\n   - **Monto recuperable estimado** si se persiguen las top 5",
            'usuario_inicial' => 'Analiza la cartera pendiente de SST y RPS, identifica las facturas que debemos priorizar para cobro y dame una estrategia concreta.',
        ],
        'estrategia' => [
            'titulo' => '🎯 Recomendaciones estratégicas',
            'descripcion' => 'Análisis profundo + roadmap accionable',
            'system' => self::OTTO_IDENTITY . "Tu tarea es dar recomendaciones estratégicas de alto nivel para mejorar la salud financiera y alcanzar el presupuesto anual.\n\nPROCEDIMIENTO:\n1. Construí el panorama completo: balance actual, tendencias de facturación y recaudo de SST, RPS y FRAMEWORK, deudas activas, muestreo de cartera.\n2. Identifica los 3 problemas/oportunidades más significativos con evidencia numérica.\n3. Para cada uno propón: acción concreta, plazo, impacto esperado en COP, riesgos.\n4. Entrega (~700 palabras):\n   - **Resumen ejecutivo** (3-4 líneas)\n   - **Análisis de cumplimiento de presupuesto del año**\n   - **3 recomendaciones estratégicas accionables** con descripción, plazo, impacto en COP, riesgos\n   - **Roadmap propuesto** para los próximos 3 meses\n   - **KPIs a vigilar** semana a semana",
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
     * Vista principal: presets + historial + consumo del mes
     */
    public function index()
    {
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
        $this->convModel->delete((int) $id); // borra mensajes por FK CASCADE
        return redirect()->to('/conciliaciones/asesoria-ia')
            ->with('success', 'Conversación eliminada.');
    }

    // ═══════════════════════════════════════════════════════════════
    // WIDGET FLOTANTE OTTO — endpoints AJAX
    // ═══════════════════════════════════════════════════════════════

    /**
     * AJAX: Inicia una conversación nueva del widget desde un preset
     */
    public function widgetIniciar()
    {
        $preset = $this->request->getPost('preset');
        if (! isset(self::PRESETS[$preset])) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Preset inválido']);
        }

        if (! $this->dentroDelBudget()) {
            return $this->budgetExcedidoJson();
        }

        $cfg = self::PRESETS[$preset];
        $userMsg = $cfg['usuario_inicial']
            ?? "Realiza el análisis '{$cfg['titulo']}' con los datos actuales del sistema.";

        return $this->ejecutarConsulta(
            null,
            $cfg['system'],
            [['role' => 'user', 'content' => $userMsg]],
            $userMsg,
            $cfg['titulo']
        );
    }

    /**
     * AJAX: Envía un mensaje del usuario en una conversación (libre o existente)
     */
    public function widgetEnviar()
    {
        $mensaje = trim((string) $this->request->getPost('mensaje'));
        $idConv  = (int) ($this->request->getPost('id_conversacion') ?? 0);

        if ($mensaje === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Mensaje vacío']);
        }
        if (mb_strlen($mensaje) > 2000) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Mensaje demasiado largo (máx 2000 caracteres)']);
        }
        if (! $this->dentroDelBudget()) {
            return $this->budgetExcedidoJson();
        }

        // Construir historial de la conversación si existe
        $messages = [];
        $convExistente = $idConv ? $this->convModel->find($idConv) : null;
        if ($convExistente) {
            $previos = $this->msgModel
                ->select('rol, contenido')
                ->where('id_conversacion', $idConv)
                ->whereIn('rol', ['user', 'assistant'])
                ->orderBy('created_at', 'ASC')
                ->findAll();
            foreach ($previos as $p) {
                if (empty($p['contenido'])) continue;
                $messages[] = ['role' => $p['rol'], 'content' => $p['contenido']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $mensaje];

        return $this->ejecutarConsulta(
            $convExistente ? $idConv : null,
            self::SYSTEM_LIBRE,
            $messages,
            $mensaje,
            'Chat libre — ' . date('d/m/Y H:i')
        );
    }

    /**
     * AJAX: Carga mensajes previos de una conversación
     */
    public function widgetMensajes($id)
    {
        $conv = $this->convModel->find((int) $id);
        if (! $conv) return $this->response->setJSON(['ok' => false, 'error' => 'Conversación no encontrada']);

        $mensajes = $this->msgModel
            ->select('rol, contenido')
            ->where('id_conversacion', (int) $id)
            ->whereIn('rol', ['user', 'assistant'])
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'ok' => true,
            'id_conversacion' => (int) $id,
            'mensajes' => $mensajes,
            'costo_mes' => round($this->msgModel->costoMesActual(), 4),
            'budget_mes' => (float) env('IA_BUDGET_MES_USD', 5.0),
        ]);
    }

    /**
     * Helper compartido: ejecuta la llamada a Claude, guarda mensajes y retorna JSON.
     */
    private function ejecutarConsulta(?int $idConvExistente, string $systemPrompt, array $messages, string $userMsg, string $tituloPorDefecto)
    {
        try {
            $ant = new AnthropicService();
            $fts = new FinancialToolsService();
            $tools = $fts->definiciones();

            $resultado = $ant->analizar(
                $systemPrompt,
                $tools,
                $messages,
                fn(string $name, array $input) => $fts->ejecutar($name, $input),
                6
            );
        } catch (\Throwable $e) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Error IA: ' . $e->getMessage()]);
        }

        $usuario = session()->get('nombre_completo') ?? session()->get('email') ?? 'sistema';

        // Crear conversación si es nueva
        if ($idConvExistente) {
            $idConv = $idConvExistente;
        } else {
            $idConv = $this->convModel->insert([
                'titulo'     => $tituloPorDefecto,
                'tipo'       => 'libre',
                'creado_por' => $usuario,
            ], true);
        }

        // Guardar el mensaje del usuario
        $this->msgModel->insert([
            'id_conversacion' => $idConv,
            'rol'             => 'user',
            'contenido'       => $userMsg,
        ]);

        // Guardar respuesta del assistant
        $this->msgModel->insert([
            'id_conversacion'    => $idConv,
            'rol'                => 'assistant',
            'contenido'          => $resultado['final_text'],
            'tool_calls'         => json_encode($this->extraerToolCalls($resultado['messages_acumulados']), JSON_UNESCAPED_UNICODE),
            'tokens_input'       => $resultado['tokens_input_total'],
            'tokens_output'      => $resultado['tokens_output_total'],
            'tokens_cache_read'  => $resultado['tokens_cache_read_total'],
            'tokens_cache_write' => $resultado['tokens_cache_write_total'],
            'modelo'             => $ant->modelo(),
            'costo_usd'          => round($resultado['costo_total_usd'], 6),
        ]);

        return $this->response->setJSON([
            'ok'              => true,
            'id_conversacion' => $idConv,
            'respuesta'       => $resultado['final_text'],
            'costo_mes'       => round($this->msgModel->costoMesActual(), 4),
            'budget_mes'      => (float) env('IA_BUDGET_MES_USD', 5.0),
        ]);
    }

    private function dentroDelBudget(): bool
    {
        $costo = $this->msgModel->costoMesActual();
        $budget = (float) env('IA_BUDGET_MES_USD', 5.0);
        return $costo < $budget;
    }

    private function budgetExcedidoJson()
    {
        $costo = $this->msgModel->costoMesActual();
        $budget = (float) env('IA_BUDGET_MES_USD', 5.0);
        return $this->response->setJSON([
            'ok' => false,
            'error' => sprintf("Límite mensual alcanzado (\$%.2f / \$%.2f USD). OTTO se reactivará el próximo mes.", $costo, $budget),
            'costo_mes' => round($costo, 4),
            'budget_mes' => $budget,
        ]);
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
