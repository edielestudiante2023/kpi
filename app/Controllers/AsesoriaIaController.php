<?php

namespace App\Controllers;

use App\Models\IaConversacionModel;
use App\Models\IaMensajeModel;
use App\Models\BalanceSnapshotModel;
use App\Services\AnthropicService;
use App\Services\FinancialToolsService;
use App\Services\CrmToolsService;
use App\Services\MarketingToolsService;
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

    // ═══════════════════════════════════════════════════════════════
    // OTTO modo COMERCIAL (CRM)
    // ═══════════════════════════════════════════════════════════════

    /** Identidad de OTTO en su rol de coach comercial */
    private const OTTO_IDENTITY_CRM = "Eres **OTTO** en su rol de **coach comercial IA** de **Cycloid Talent** (empresa colombiana de servicios SST y reclutamiento RPS). Hablas en español Colombia con tono ejecutivo, claro y directo. Te refieres a montos en pesos colombianos con separador de miles (\$1.234.567).\n\n" .
        "**Tu misión principal** es responder dos preguntas críticas:\n" .
        "1. **¿Avanzamos?** — comparar el estado actual del pipeline con snapshots anteriores y ser honesto sobre si realmente estamos progresando (no solo describir el estado, evaluarlo).\n" .
        "2. **¿Qué hacer para crecer?** — proponer acciones concretas y priorizadas para mover el pipeline.\n\n" .
        "Tienes acceso a los datos reales vía herramientas que debes usar antes de responder con números. NUNCA inventes cifras. Sé conciso pero accionable.\n\n" .
        "HERRAMIENTAS DISPONIBLES (úsalas SIEMPRE antes de responder con números):\n" .
        "• `obtener_snapshot_semanal(fecha?)` — recupera un snapshot histórico\n" .
        "• `comparar_snapshots(fecha_a?, fecha_b?)` — la tool clave para '¿avanzamos?'\n" .
        "• `obtener_pipeline_actual` — funnel vivo por etapa\n" .
        "• `obtener_oportunidades_estancadas(dias_min?)` — sin actividad reciente\n" .
        "• `obtener_top_oportunidades(criterio)` — ranking (valor_ponderado es lo mejor para priorizar)\n" .
        "• `obtener_oportunidades_proximas_cierre(dias?)` — urgencia de cierre\n" .
        "• `obtener_ranking_responsables(periodo?)` — top performers vs sin movimiento\n" .
        "• `obtener_motivos_perdida_top(periodo?)` — patrones de pérdida\n" .
        "• `obtener_oportunidad_detalle(codigo)` — ficha 360 con timeline (códigos OPP-YYYYMMDD-NNNN)\n" .
        "• `obtener_empresa_actividad(id_empresa | nombre)` — historial completo con un cliente\n\n";

    /** Prompt conversación libre del widget en modo comercial */
    private const SYSTEM_LIBRE_CRM = self::OTTO_IDENTITY_CRM .
        "Estás en modo conversacional dentro del widget, asistiendo al equipo comercial. Reglas:\n" .
        "- Antes de dar números, llama a las tools relevantes\n" .
        "- Si preguntan '¿avanzamos?' / '¿cómo vamos?', usa primero `comparar_snapshots`\n" .
        "- Si mencionan un código de oportunidad (OPP-...), usa `obtener_oportunidad_detalle`\n" .
        "- Si preguntan por una empresa/cliente, usa `obtener_empresa_actividad`\n" .
        "- Markdown corto (negritas, listas, tablas pequeñas)\n" .
        "- Máximo 250 palabras salvo que la pregunta exija más detalle\n" .
        "- Cierra con 'Acciones sugeridas:' + 2-3 bullets concretos cuando aplique\n" .
        "- Si la pregunta es ambigua, hacé 1 sola contra-pregunta clarificadora\n" .
        "- Si no tiene que ver con ventas/pipeline, redirige amablemente";

    /** Presets comerciales del CRM (chips del widget) */
    private const PRESETS_CRM = [
        'avanzamos' => [
            'titulo' => '📈 ¿Avanzamos esta semana?',
            'descripcion' => 'Compara snapshots y diagnostica progreso real',
            'system' => self::OTTO_IDENTITY_CRM . "Tu tarea es responder honestamente '¿avanzamos?' usando los snapshots disponibles.\n\nPROCEDIMIENTO:\n1. Llama a `comparar_snapshots()` (sin fechas para comparar los dos más recientes).\n2. Si solo hay 1 snapshot, di claramente que no se puede comparar y sugiere generar el siguiente la próxima semana.\n3. Analiza cada delta: ¿positivo o negativo?, ¿significativo?\n4. Entrega (~300 palabras):\n   - **Veredicto** (1 línea: AVANZAMOS / ESTANCADOS / RETROCEDIMOS)\n   - **Lo que mejoró** (bullets con evidencia numérica)\n   - **Lo que empeoró o se mantiene** (bullets)\n   - **Acciones sugeridas** (2-3 bullets concretos para la próxima semana)",
            'usuario_inicial' => '¿Avanzamos esta semana en el pipeline comercial? Compara los snapshots más recientes y dame un veredicto honesto con evidencia.',
        ],
        'prioridades' => [
            'titulo' => '🎯 ¿Qué oportunidades atacar primero?',
            'descripcion' => 'Prioriza por valor ponderado y urgencia de cierre',
            'system' => self::OTTO_IDENTITY_CRM . "Tu tarea es identificar las oportunidades donde concentrar el esfuerzo comercial inmediato.\n\nPROCEDIMIENTO:\n1. Llama a `obtener_top_oportunidades` con criterio 'valor_ponderado', límite 10.\n2. Llama a `obtener_oportunidades_proximas_cierre` con dias=30 para detectar urgencias.\n3. Llama a `obtener_oportunidades_estancadas` (dias_min 21) para detectar las que están muriendo.\n4. Entrega (~400 palabras):\n   - **Top 3 oportunidades a cerrar YA** (alto valor ponderado + cierre cercano)\n   - **Top 3 oportunidades en riesgo de morir** (alto valor + estancadas)\n   - **Acción recomendada por cada una** (qué hacer esta semana)",
            'usuario_inicial' => '¿Qué oportunidades debería atacar primero esta semana? Prioriza por valor ponderado y urgencia de cierre, y dame acciones concretas.',
        ],
        'diagnostico_equipo' => [
            'titulo' => '👥 Diagnóstico del equipo',
            'descripcion' => 'Quién está vendiendo, quién no mueve nada',
            'system' => self::OTTO_IDENTITY_CRM . "Tu tarea es diagnosticar el desempeño del equipo comercial.\n\nPROCEDIMIENTO:\n1. Llama a `obtener_ranking_responsables` periodo 'mes_actual'.\n2. Llama también con periodo 'anio' para contexto largo.\n3. Identifica: top performer, en alza, sin movimiento.\n4. Entrega (~400 palabras):\n   - **Tabla ranking del mes** (ganadas, valor ganado, pipeline en curso)\n   - **Top performer** del mes y por qué destaca\n   - **Vendedor(es) sin movimiento** y posibles causas\n   - **Recomendaciones** (apoyo, reasignación, capacitación) con tono constructivo, no acusatorio",
            'usuario_inicial' => 'Diagnostica el desempeño del equipo de ventas: ¿quién está vendiendo, quién no, qué hacer al respecto?',
        ],
        'cuellos_botella' => [
            'titulo' => '🚧 Cuellos de botella del pipeline',
            'descripcion' => 'Dónde se atascan las oportunidades y por qué se caen',
            'system' => self::OTTO_IDENTITY_CRM . "Tu tarea es detectar dónde se atasca el pipeline y por qué.\n\nPROCEDIMIENTO:\n1. Llama a `obtener_pipeline_actual` para ver distribución por etapa.\n2. Llama a `obtener_oportunidades_estancadas` (dias_min 30).\n3. Llama a `obtener_motivos_perdida_top` periodo 'anio' para entender por qué se cae el cierre.\n4. Entrega (~400 palabras):\n   - **Mapa del funnel** (qué etapa tiene mucho stock vs poco flujo)\n   - **Etapa con mayor estancamiento**\n   - **Motivos de pérdida recurrentes** (patrón a corregir)\n   - **3 acciones correctivas** específicas por cuello detectado",
            'usuario_inicial' => '¿Dónde se atasca el pipeline? Identifica los cuellos de botella por etapa, las oportunidades estancadas y los motivos de pérdida más recurrentes.',
        ],
        'plan_crecimiento' => [
            'titulo' => '🚀 Plan de crecimiento del mes',
            'descripcion' => 'Síntesis ejecutiva + acciones priorizadas para el mes',
            'system' => self::OTTO_IDENTITY_CRM . "Tu tarea es construir un plan de crecimiento del mes con foco en mover el pipeline.\n\nPROCEDIMIENTO:\n1. Llama a `comparar_snapshots` para entender tendencia reciente.\n2. Llama a `obtener_pipeline_actual` para estado vivo.\n3. Llama a `obtener_top_oportunidades` criterio 'valor_ponderado'.\n4. Llama a `obtener_oportunidades_proximas_cierre` dias=45.\n5. Llama a `obtener_ranking_responsables` periodo 'mes_actual'.\n6. Entrega (~600 palabras):\n   - **Resumen ejecutivo** (3-4 líneas con situación y tendencia)\n   - **Objetivos numéricos del mes** (cantidad y valor a cerrar — sé realista basándote en pipeline ponderado)\n   - **3 acciones priorizadas** con descripción, responsable sugerido, plazo, impacto en COP esperado\n   - **Riesgos** a vigilar\n   - **KPIs a revisar cada semana**",
            'usuario_inicial' => 'Constrúyeme un plan de crecimiento del mes: resumen ejecutivo, objetivos numéricos realistas, 3 acciones priorizadas y KPIs a vigilar.',
        ],
    ];

    // ═══════════════════════════════════════════════════════════════
    // OTTO modo MARKETING (coach de marketing para Solangel)
    // ═══════════════════════════════════════════════════════════════

    /** Identidad de OTTO en su rol de coach de marketing */
    private const OTTO_IDENTITY_MARKETING = "Eres **OTTO** en su rol de **coach de marketing IA** de **Cycloid Talent** (empresa colombiana de 5 personas, servicios SST y reclutamiento RPS). El equipo es pequeño y artesanal en marketing — tu interlocutora principal es Solangel, encargada de marketing sin formación formal en el área. Hablas en español Colombia con tono didáctico, cálido y ejecutivo. Te refieres a montos en pesos colombianos con separador de miles (\$1.234.567).\n\n" .
        "**Tu misión principal** es responder dos preguntas críticas:\n" .
        "1. **¿Avanzamos?** — comparar leads y acciones de esta semana con la pasada, y ser honesto sobre si hay progreso o estamos estancados.\n" .
        "2. **¿Qué hacer para crecer?** — proponer acciones de marketing concretas y priorizadas según lo que está funcionando (no por intuición).\n\n" .
        "REGLA CLAVE para empresa pequeña: **la fuente que CALIFICA mejor vale más que la que trae más volumen**. Si LinkedIn trae 20 leads y solo 1 califica, pero referidos traen 3 y 2 califican, los referidos valen ×10.\n\n" .
        "Tienes acceso a los datos reales vía herramientas que debes usar antes de responder con números. NUNCA inventes cifras. Sé conciso pero accionable.\n\n" .
        "HERRAMIENTAS DISPONIBLES:\n" .
        "• `obtener_resumen_semanal(fecha?)` — leads nuevos, calificados, acciones y costo de una semana\n" .
        "• `comparar_semanas(fecha_a?, fecha_b?)` — la tool clave para '¿avanzamos?'\n" .
        "• `obtener_embudo_actual` — estado del funnel hoy (nuevo/contactado/calificado/descartado)\n" .
        "• `obtener_fuentes_por_calificacion(limite?)` — ranking de fuentes por CALIFICADOS (no por volumen)\n" .
        "• `obtener_leads_estancados(dias_min?)` — leads sin actualizar que se están enfriando\n" .
        "• `obtener_acciones_periodo(desde, hasta)` — diario detallado de un rango\n" .
        "• `obtener_resumen_acciones_por_tipo(desde, hasta)` — agregado por tipo de acción con costo y leads atribuidos\n" .
        "• `calcular_cac(anio?, mes?)` — CAC informal del mes (costo acciones / leads nuevos)\n\n";

    /** Prompt conversación libre del widget en modo marketing */
    private const SYSTEM_LIBRE_MARKETING = self::OTTO_IDENTITY_MARKETING .
        "Estás en modo conversacional dentro del widget, asistiendo principalmente a Solangel. Reglas:\n" .
        "- Antes de dar números, llama a las tools relevantes\n" .
        "- Si preguntan '¿avanzamos?' / '¿cómo voy?', usa primero `comparar_semanas`\n" .
        "- Si preguntan '¿qué hago esta semana?', revisa el embudo, las acciones recientes y los leads estancados antes de proponer\n" .
        "- Si Solangel pregunta cosas básicas de marketing (qué es un CAC, qué es calificar, etc.), explica con ejemplos del propio negocio\n" .
        "- Markdown corto (negritas, listas, tablas pequeñas)\n" .
        "- Máximo 250 palabras salvo que la pregunta exija más detalle\n" .
        "- Cierra con 'Acciones sugeridas:' + 2-3 bullets accionables\n" .
        "- Tono motivador pero honesto: si estamos mal, dilo claro; si vamos bien, celebra\n" .
        "- Si la pregunta no tiene que ver con marketing / leads / pipeline, redirige amablemente";

    /** Presets del widget en modo marketing */
    private const PRESETS_MARKETING = [
        'avanzamos' => [
            'titulo' => '📈 ¿Avanzamos esta semana?',
            'descripcion' => 'Compara semana actual vs pasada — leads, calificación y acciones',
            'system' => self::OTTO_IDENTITY_MARKETING . "Tu tarea es responder honestamente '¿avanzamos?' usando la comparación de semanas.\n\nPROCEDIMIENTO:\n1. Llama a `comparar_semanas()` sin fechas (compara semana pasada vs actual).\n2. Analiza cada delta: leads, tasa de calificación, acciones, costo.\n3. Entrega (~300 palabras):\n   - **Veredicto** (1 línea: AVANZAMOS / ESTANCADOS / RETROCEDIMOS)\n   - **Lo que mejoró** (con evidencia numérica)\n   - **Lo que empeoró o se mantiene**\n   - **Acciones sugeridas** para la próxima semana (2-3 bullets concretos)",
            'usuario_inicial' => '¿Avanzamos esta semana en marketing? Compara semana pasada vs esta semana y dame un veredicto honesto con evidencia.',
        ],
        'fuentes_efectivas' => [
            'titulo' => '🎯 ¿Qué fuente está funcionando?',
            'descripcion' => 'Identifica de dónde vienen los leads que SÍ califican',
            'system' => self::OTTO_IDENTITY_MARKETING . "Tu tarea es identificar las fuentes de lead más efectivas (no las más voluminosas).\n\nPROCEDIMIENTO:\n1. Llama a `obtener_fuentes_por_calificacion(limite=10)`.\n2. Llama también a `obtener_embudo_actual` para contexto.\n3. Identifica: fuente estrella (mucho califica), fuente que solo trae volumen sin calificar, fuente subexplotada (poco volumen pero gran tasa).\n4. Entrega (~350 palabras):\n   - **Tabla con top 5 fuentes** (total, calificados, tasa)\n   - **La estrella** (fuente con más calificados absolutos)\n   - **La trampa** (fuente con mucho volumen pero baja tasa — gastar menos energía ahí)\n   - **La oportunidad** (fuente con poco volumen pero gran tasa — duplicar esfuerzo)\n   - **Acción sugerida** específica para cada caso",
            'usuario_inicial' => '¿Qué fuente está funcionando mejor para generar leads que califican? Identifica la estrella, la trampa y la oportunidad subexplotada.',
        ],
        'leads_olvidados' => [
            'titulo' => '🥶 Leads que se están enfriando',
            'descripcion' => 'Lista de leads sin contacto reciente que pueden estar muriendo',
            'system' => self::OTTO_IDENTITY_MARKETING . "Tu tarea es identificar los leads que se están enfriando por falta de seguimiento.\n\nPROCEDIMIENTO:\n1. Llama a `obtener_leads_estancados(dias_min=7, limite=20)`.\n2. Si la lista está vacía, felicita pero advierte que igual conviene revisar leads en 'contactado' que llevan más de 14 días sin pasar a 'calificado'.\n3. Entrega (~350 palabras):\n   - **Top 5 leads en riesgo** (tabla: nombre, días sin actualizar, fuente, último estado conocido)\n   - **Patrón detectado** (¿son todos de la misma fuente? ¿del mismo responsable?)\n   - **Plan de rescate** (mensaje sugerido para reactivar, orden de prioridad por días)",
            'usuario_inicial' => '¿Qué leads se están enfriando por falta de seguimiento? Dame los más urgentes y un plan para reactivarlos.',
        ],
        'diagnostico_acciones' => [
            'titulo' => '🔍 ¿Estoy haciendo lo suficiente?',
            'descripcion' => 'Revisa tu diario de acciones del mes y diagnóstica',
            'system' => self::OTTO_IDENTITY_MARKETING . "Tu tarea es diagnosticar si Solangel está haciendo suficiente actividad de marketing y bien distribuida.\n\nPROCEDIMIENTO:\n1. Llama a `obtener_resumen_acciones_por_tipo(desde, hasta)` con el mes actual.\n2. Llama también a `comparar_semanas` para tendencia.\n3. Entrega (~400 palabras):\n   - **Volumen total** del mes vs un benchmark razonable (para empresa pequeña: 8-15 acciones/mes)\n   - **Distribución por tipo** (¿está muy concentrada en una sola cosa? ¿faltan algunos tipos?)\n   - **Tendencia semanal** (¿ha caído la actividad en las últimas semanas?)\n   - **Veredicto** (suficiente / insuficiente / poco diversa) con tono constructivo\n   - **3 acciones específicas** para la próxima semana, mezclando tipos",
            'usuario_inicial' => 'Diagnostica mi actividad de marketing del mes: ¿estoy haciendo suficiente?, ¿lo estoy distribuyendo bien?, ¿qué me falta?',
        ],
        'plan_semana' => [
            'titulo' => '🚀 Plan de marketing de esta semana',
            'descripcion' => 'Síntesis ejecutiva + 3-5 acciones priorizadas para los próximos 7 días',
            'system' => self::OTTO_IDENTITY_MARKETING . "Tu tarea es darle a Solangel un plan accionable para esta semana, basado en datos.\n\nPROCEDIMIENTO:\n1. Llama a `obtener_resumen_semanal` (semana actual).\n2. Llama a `obtener_fuentes_por_calificacion(limite=5)`.\n3. Llama a `obtener_leads_estancados(dias_min=7, limite=10)`.\n4. Llama a `obtener_embudo_actual`.\n5. Entrega (~500 palabras):\n   - **Diagnóstico breve** (2 líneas con la situación)\n   - **3-5 acciones priorizadas** para esta semana — cada una con: qué hacer concretamente (no 'hacer más marketing' sino 'mandar 5 correos a leads en estado contactado de la empresa X'), por qué (basado en los datos), tiempo estimado\n   - **Métricas a vigilar** al final de la semana (qué debería mejorar si las acciones funcionan)",
            'usuario_inicial' => 'Constrúyeme un plan accionable de marketing para esta semana: 3-5 acciones concretas basadas en los datos actuales, con qué hacer y por qué.',
        ],
    ];

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
        $data['backUrl']   = '/conciliaciones/asesoria-ia';

        return view('conciliaciones/asesoria_ia_conversacion', $data);
    }

    // ═══════════════════════════════════════════════════════════════
    // OTTO modo COMERCIAL — endpoints específicos del CRM
    // ═══════════════════════════════════════════════════════════════

    /** Vista principal del asesor IA en modo comercial */
    public function indexCrm()
    {
        helper('crm');
        if (!crm_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo CRM.');
        }

        $data['presets']    = self::PRESETS_CRM;
        $data['historial']  = $this->convModel
            ->where('contexto', 'comercial')
            ->orderBy('created_at', 'DESC')
            ->findAll(20);
        $data['costoMes']   = $this->msgModel->costoMesActual();
        $data['budgetMes']  = (float) env('IA_BUDGET_MES_USD', 5.0);
        $data['porcentaje'] = $data['budgetMes'] > 0
            ? min(100, round($data['costoMes'] / $data['budgetMes'] * 100, 1))
            : 0;

        return view('crm/asesor_ia', $data);
    }

    /** Dispara un preset comercial y crea la conversación */
    public function analizarCrm()
    {
        helper('crm');
        if (!crm_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo CRM.');
        }

        $preset = $this->request->getPost('preset');
        if (!isset(self::PRESETS_CRM[$preset])) {
            return redirect()->back()->with('errors', ['Preset inválido.']);
        }

        // Budget check
        $costoActual = $this->msgModel->costoMesActual();
        $budget = (float) env('IA_BUDGET_MES_USD', 5.0);
        if ($costoActual >= $budget) {
            return redirect()->back()->with('errors', [
                sprintf("Límite mensual alcanzado (\$%.2f / \$%.2f USD). OTTO se reactivará el próximo mes.",
                    $costoActual, $budget)
            ]);
        }

        $cfg = self::PRESETS_CRM[$preset];
        $userMessage = $cfg['usuario_inicial'];

        try {
            $ant = new AnthropicService();
            $service = new CrmToolsService();
            $tools = $service->definiciones();
            $resultado = $ant->analizar(
                $cfg['system'],
                $tools,
                [['role' => 'user', 'content' => $userMessage]],
                fn(string $name, array $input) => $service->ejecutar($name, $input),
                6
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('errors', ['Error IA: ' . $e->getMessage()]);
        }

        $usuario = session()->get('nombre_completo') ?? session()->get('email') ?? 'sistema';
        $idConv = $this->convModel->insert([
            'titulo'     => $cfg['titulo'] . ' — ' . date('d/m/Y H:i'),
            'tipo'       => $preset,
            'contexto'   => 'comercial',
            'creado_por' => $usuario,
        ], true);

        $this->msgModel->insert([
            'id_conversacion' => $idConv,
            'rol'             => 'user',
            'contenido'       => $userMessage,
        ]);

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

        return redirect()->to("/crm/asesor-ia/ver/{$idConv}");
    }

    /** Ver conversación CRM — reusa la vista pero con back-link a /crm */
    public function verCrm($id)
    {
        helper('crm');
        if (!crm_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo CRM.');
        }
        $idConv = (int) $id;
        $data['conversacion'] = $this->convModel->find($idConv);
        if (! $data['conversacion']) {
            return redirect()->to('/crm/asesor-ia')->with('errors', ['Conversación no encontrada.']);
        }
        $data['mensajes'] = $this->msgModel
            ->where('id_conversacion', $idConv)
            ->orderBy('created_at', 'ASC')->findAll();
        $data['costoMes']  = $this->msgModel->costoMesActual();
        $data['budgetMes'] = (float) env('IA_BUDGET_MES_USD', 5.0);
        $data['backUrl']   = '/crm/asesor-ia';

        return view('conciliaciones/asesoria_ia_conversacion', $data);
    }

    /** Eliminar conversación CRM */
    public function eliminarCrm($id)
    {
        helper('crm');
        if (!crm_tiene_acceso()) {
            return redirect()->to('/login');
        }
        $this->convModel->delete((int) $id);
        return redirect()->to('/crm/asesor-ia')->with('success', 'Conversación eliminada.');
    }

    // ═══════════════════════════════════════════════════════════════
    // OTTO modo MARKETING — endpoints específicos
    // ═══════════════════════════════════════════════════════════════

    /** Vista principal del asesor IA en modo marketing */
    public function indexMarketing()
    {
        helper('marketing');
        if (!marketing_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo Marketing.');
        }

        $data['presets']    = self::PRESETS_MARKETING;
        $data['historial']  = $this->convModel
            ->where('contexto', 'marketing')
            ->orderBy('created_at', 'DESC')
            ->findAll(20);
        $data['costoMes']   = $this->msgModel->costoMesActual();
        $data['budgetMes']  = (float) env('IA_BUDGET_MES_USD', 5.0);
        $data['porcentaje'] = $data['budgetMes'] > 0
            ? min(100, round($data['costoMes'] / $data['budgetMes'] * 100, 1))
            : 0;

        return view('marketing/asesor_ia', $data);
    }

    /** Dispara un preset de marketing y crea la conversación */
    public function analizarMarketing()
    {
        helper('marketing');
        if (!marketing_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo Marketing.');
        }

        $preset = $this->request->getPost('preset');
        if (!isset(self::PRESETS_MARKETING[$preset])) {
            return redirect()->back()->with('errors', ['Preset inválido.']);
        }

        // Budget check
        $costoActual = $this->msgModel->costoMesActual();
        $budget = (float) env('IA_BUDGET_MES_USD', 5.0);
        if ($costoActual >= $budget) {
            return redirect()->back()->with('errors', [
                sprintf("Límite mensual alcanzado (\$%.2f / \$%.2f USD). OTTO se reactivará el próximo mes.",
                    $costoActual, $budget)
            ]);
        }

        $cfg = self::PRESETS_MARKETING[$preset];
        $userMessage = $cfg['usuario_inicial'];

        try {
            $ant = new AnthropicService();
            $service = new MarketingToolsService();
            $tools = $service->definiciones();
            $resultado = $ant->analizar(
                $cfg['system'],
                $tools,
                [['role' => 'user', 'content' => $userMessage]],
                fn(string $name, array $input) => $service->ejecutar($name, $input),
                6
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('errors', ['Error IA: ' . $e->getMessage()]);
        }

        $usuario = session()->get('nombre_completo') ?? session()->get('email') ?? 'sistema';
        $idConv = $this->convModel->insert([
            'titulo'     => $cfg['titulo'] . ' — ' . date('d/m/Y H:i'),
            'tipo'       => $preset,
            'contexto'   => 'marketing',
            'creado_por' => $usuario,
        ], true);

        $this->msgModel->insert([
            'id_conversacion' => $idConv,
            'rol'             => 'user',
            'contenido'       => $userMessage,
        ]);

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

        return redirect()->to("/marketing/asesor-ia/ver/{$idConv}");
    }

    /** Ver conversación marketing — reusa la vista pero con back-link a /marketing */
    public function verMarketing($id)
    {
        helper('marketing');
        if (!marketing_tiene_acceso()) {
            return redirect()->to('/login')->with('error', 'No tienes acceso al módulo Marketing.');
        }
        $idConv = (int) $id;
        $data['conversacion'] = $this->convModel->find($idConv);
        if (! $data['conversacion']) {
            return redirect()->to('/marketing/asesor-ia')->with('errors', ['Conversación no encontrada.']);
        }
        $data['mensajes'] = $this->msgModel
            ->where('id_conversacion', $idConv)
            ->orderBy('created_at', 'ASC')->findAll();
        $data['costoMes']  = $this->msgModel->costoMesActual();
        $data['budgetMes'] = (float) env('IA_BUDGET_MES_USD', 5.0);
        $data['backUrl']   = '/marketing/asesor-ia';

        return view('conciliaciones/asesoria_ia_conversacion', $data);
    }

    /** Eliminar conversación marketing */
    public function eliminarMarketing($id)
    {
        helper('marketing');
        if (!marketing_tiene_acceso()) {
            return redirect()->to('/login');
        }
        $this->convModel->delete((int) $id);
        return redirect()->to('/marketing/asesor-ia')->with('success', 'Conversación eliminada.');
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
        $contextoIn = $this->request->getPost('contexto');
        $contexto = in_array($contextoIn, ['comercial', 'marketing'], true) ? $contextoIn : 'financiero';
        $presets = match ($contexto) {
            'marketing' => self::PRESETS_MARKETING,
            'comercial' => self::PRESETS_CRM,
            default     => self::PRESETS,
        };

        if (! isset($presets[$preset])) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Preset inválido']);
        }

        if (! $this->dentroDelBudget()) {
            return $this->budgetExcedidoJson();
        }

        $cfg = $presets[$preset];
        $userMsg = $cfg['usuario_inicial']
            ?? "Realiza el análisis '{$cfg['titulo']}' con los datos actuales del sistema.";

        return $this->ejecutarConsulta(
            null,
            $cfg['system'],
            [['role' => 'user', 'content' => $userMsg]],
            $userMsg,
            $cfg['titulo'],
            $contexto
        );
    }

    /**
     * AJAX: Envía un mensaje del usuario en una conversación (libre o existente)
     */
    public function widgetEnviar()
    {
        $mensaje = trim((string) $this->request->getPost('mensaje'));
        $idConv  = (int) ($this->request->getPost('id_conversacion') ?? 0);
        $ctxRaw = $this->request->getPost('contexto');
        $contextoInput = in_array($ctxRaw, ['comercial', 'marketing'], true) ? $ctxRaw : 'financiero';

        if ($mensaje === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Mensaje vacío']);
        }
        if (mb_strlen($mensaje) > 2000) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Mensaje demasiado largo (máx 2000 caracteres)']);
        }
        if (! $this->dentroDelBudget()) {
            return $this->budgetExcedidoJson();
        }

        // Construir historial + detectar contexto de la conversación
        $messages = [];
        $convExistente = $idConv ? $this->convModel->find($idConv) : null;
        $contexto = $convExistente ? ($convExistente['contexto'] ?? 'financiero') : $contextoInput;

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

        $systemPrompt = match ($contexto) {
            'marketing' => self::SYSTEM_LIBRE_MARKETING,
            'comercial' => self::SYSTEM_LIBRE_CRM,
            default     => self::SYSTEM_LIBRE,
        };
        $prefijo = match ($contexto) {
            'marketing' => 'Marketing — ',
            'comercial' => 'CRM — ',
            default     => 'Chat libre — ',
        };
        $titulo = $prefijo . date('d/m/Y H:i');

        return $this->ejecutarConsulta(
            $convExistente ? $idConv : null,
            $systemPrompt,
            $messages,
            $mensaje,
            $titulo,
            $contexto
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
     * $contexto switchea entre tools financieros (default) o tools comerciales (CRM).
     */
    private function ejecutarConsulta(?int $idConvExistente, string $systemPrompt, array $messages, string $userMsg, string $tituloPorDefecto, string $contexto = 'financiero')
    {
        try {
            $ant = new AnthropicService();
            $service = match ($contexto) {
                'marketing' => new MarketingToolsService(),
                'comercial' => new CrmToolsService(),
                default     => new FinancialToolsService(),
            };
            $tools = $service->definiciones();

            $resultado = $ant->analizar(
                $systemPrompt,
                $tools,
                $messages,
                fn(string $name, array $input) => $service->ejecutar($name, $input),
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
                'contexto'   => $contexto,
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
