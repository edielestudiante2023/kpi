<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual del usuario – Marketing – Kpi Cycloid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .manual-wrap { max-width: 1200px; margin: 0 auto; }
        .toc {
            position: sticky; top: 16px;
            background: #fff; border-radius: 8px; padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            max-height: calc(100vh - 32px); overflow-y: auto;
        }
        .toc h6 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6c757d; margin-bottom: 10px; }
        .toc a { display: block; padding: 4px 8px; font-size: 0.85rem; color: #495057; text-decoration: none; border-radius: 4px; }
        .toc a:hover { background: #e7f1ff; color: #0d6efd; }
        .toc a.active { background: #0d6efd; color: #fff; font-weight: 600; }
        .toc ul { list-style: none; padding-left: 12px; margin: 4px 0; }
        .seccion {
            background: #fff; border-radius: 8px; padding: 24px 28px;
            margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            scroll-margin-top: 16px;
        }
        .seccion h2 {
            color: #2c3e50; font-size: 1.4rem; font-weight: 700;
            margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #0d6efd;
        }
        .seccion h3 {
            color: #2c3e50; font-size: 1.1rem; font-weight: 600;
            margin-top: 22px; margin-bottom: 10px;
        }
        .paso {
            background: #f8f9fa; border-left: 4px solid #0d6efd;
            padding: 10px 14px; border-radius: 4px; margin-bottom: 10px;
            position: relative;
        }
        .paso-num {
            position: absolute; left: -16px; top: -10px;
            background: #0d6efd; color: #fff; width: 28px; height: 28px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .paso h5 { font-size: 0.95rem; margin-bottom: 4px; color: #2c3e50; }
        .paso p { margin-bottom: 4px; font-size: 0.92rem; }
        .tip {
            background: #d1ecf1; border-left: 4px solid #0c5460;
            padding: 10px 14px; border-radius: 4px; margin: 10px 0;
            font-size: 0.9rem;
        }
        .tip strong { color: #0c5460; }
        .warning {
            background: #fff3cd; border-left: 4px solid #856404;
            padding: 10px 14px; border-radius: 4px; margin: 10px 0;
            font-size: 0.9rem;
        }
        .warning strong { color: #856404; }
        .ejemplo {
            background: #e8f5e9; border-left: 4px solid #198754;
            padding: 10px 14px; border-radius: 4px; margin: 10px 0;
            font-size: 0.9rem;
        }
        .ejemplo strong { color: #198754; }
        kbd {
            background: #2c3e50; color: #fff; padding: 2px 6px;
            border-radius: 3px; font-size: 0.78rem;
        }
        .ruta-ui {
            display: inline-block; background: #e9ecef; padding: 1px 8px;
            border-radius: 3px; font-family: monospace; font-size: 0.85rem;
            color: #495057;
        }
        .seccion ol { padding-left: 22px; }
        .seccion ol li { margin-bottom: 5px; font-size: 0.92rem; }
        .seccion ul li { font-size: 0.92rem; margin-bottom: 4px; }
        .estado-badge {
            display: inline-block; padding: 2px 8px; border-radius: 12px;
            font-size: 0.72rem; font-weight: 600; color: #fff;
        }
    </style>
</head>
<body>
<?= $this->include('partials/nav') ?>

<div class="container-fluid py-3 manual-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="bi bi-book me-2"></i>Manual del usuario — Marketing</h1>
        <a href="<?= base_url('marketing/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver al Marketing
        </a>
    </div>

    <p class="text-muted small mb-4">
        Guía paso a paso del módulo Marketing, pensada para Solangel y cualquiera del equipo que
        quiera entender el embudo de leads. <strong>Si no sabes por dónde empezar, lee al menos las
        secciones 1, 2, 5 y 7 — con eso ya puedes usar el módulo.</strong>
    </p>

    <div class="row g-3">
        <!-- TOC -->
        <div class="col-md-3">
            <nav class="toc" id="toc">
                <h6>Contenido</h6>
                <a href="#intro">1. Para qué sirve</a>
                <a href="#entrar">2. Cómo entrar</a>
                <a href="#embudo">3. El embudo (los 4 estados)</a>
                <a href="#capturar">4. Capturar un lead</a>
                <a href="#mover">5. Mover un lead por el embudo</a>
                <a href="#convertir">6. Convertir lead → oportunidad CRM</a>
                <a href="#diario">7. El diario de acciones</a>
                <ul>
                    <li><a href="#diario-que">7.1 Qué anotar</a></li>
                    <li><a href="#diario-costo">7.2 Cuándo poner costo</a></li>
                </ul>
                <a href="#dashboard">8. Leer el dashboard</a>
                <a href="#otto">9. OTTO Coach de Marketing</a>
                <ul>
                    <li><a href="#otto-uso">9.1 Cómo usarlo</a></li>
                    <li><a href="#otto-presets">9.2 Los 5 análisis</a></li>
                </ul>
                <a href="#disciplina">10. Disciplina semanal</a>
                <a href="#faq">11. Preguntas frecuentes</a>
            </nav>
        </div>

        <!-- Contenido -->
        <div class="col-md-9">

        <!-- 1. INTRO -->
        <section class="seccion" id="intro">
            <h2>1. ¿Para qué sirve este módulo?</h2>
            <p>El módulo Marketing es <strong>la herramienta de Solangel</strong> (y de cualquiera que ayude con marketing) para responder <strong>tres preguntas</strong>:</p>
            <ol>
                <li><strong>¿De dónde vienen los leads?</strong> — qué canal funciona mejor.</li>
                <li><strong>¿Avanzamos esta semana?</strong> — ¿estamos generando más interés que la semana pasada?</li>
                <li><strong>¿Qué hago la próxima semana?</strong> — qué acciones priorizar para crecer.</li>
            </ol>

            <div class="tip">
                <strong>💡 Filosofía del módulo:</strong> está hecho a la medida de una empresa pequeña (5 personas).
                No es un CRM de marketing gigante. Solo lo mínimo para <strong>capturar interés</strong>,
                <strong>medirlo</strong>, y <strong>aprender qué funciona</strong>.
            </div>

            <p>Tiene 4 pantallas principales:</p>
            <ul>
                <li><strong>Dashboard</strong> — los 5 números que importan, de un vistazo.</li>
                <li><strong>Leads</strong> — las personas/empresas que mostraron interés.</li>
                <li><strong>Diario de acciones</strong> — el log de lo que vas haciendo (posts, eventos, llamadas).</li>
                <li><strong>OTTO Coach</strong> — el asistente IA que mira los datos y te dice qué hacer.</li>
            </ul>
        </section>

        <!-- 2. ENTRAR -->
        <section class="seccion" id="entrar">
            <h2>2. Cómo entrar al módulo</h2>
            <ol>
                <li>Inicia sesión en <span class="ruta-ui">kpi.cycloidtalent.com</span>.</li>
                <li>En el menú superior verás el dropdown <kbd><i class="bi bi-megaphone"></i> Marketing</kbd>. Todos los usuarios tienen acceso automáticamente, excepto el contador.</li>
                <li>Al abrir el dropdown verás:
                    <ul>
                        <li><strong>Dashboard:</strong> los 5 KPIs y gráficos.</li>
                        <li><strong>OTTO Coach de Marketing:</strong> el asistente IA.</li>
                        <li><strong>Leads:</strong> lista de personas/empresas interesadas.</li>
                        <li><strong>Nuevo lead:</strong> atajo para capturar.</li>
                        <li><strong>Diario de acciones:</strong> log de lo que has hecho.</li>
                        <li><strong>Registrar acción:</strong> atajo para anotar.</li>
                        <li><strong>Manual de usuario</strong> — este documento.</li>
                        <li><strong>Configuración</strong> (solo admin): editar tipos de acción.</li>
                    </ul>
                </li>
            </ol>
        </section>

        <!-- 3. EMBUDO -->
        <section class="seccion" id="embudo">
            <h2>3. El embudo: los 4 estados de un lead</h2>
            <p>Un <strong>lead</strong> es una persona o empresa que mostró interés en Cycloid Talent (te
            escribieron, te llamaron, llenaron un formulario, alguien los refirió, etc.). Todo lead pasa por
            estos 4 estados:</p>

            <div class="paso"><span class="paso-num">1</span>
                <h5><span class="estado-badge" style="background:#0dcaf0;color:#000;">Nuevo</span></h5>
                <p>Acaba de aparecer. Aún no lo has contactado. <strong>No puede quedarse aquí mucho tiempo</strong> — si no lo contactas en 3-5 días, se enfría.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5><span class="estado-badge" style="background:#fd7e14;">Contactado</span></h5>
                <p>Ya hablaste con él/ella al menos una vez. Estás en proceso de entender si realmente tiene necesidad y presupuesto.</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5><span class="estado-badge" style="background:#198754;">Calificado</span></h5>
                <p><strong>Es una oportunidad real</strong>: tiene necesidad, presupuesto y autoridad para decidir.
                Cuando llega a este estado, normalmente lo <a href="#convertir">conviertes a oportunidad CRM</a> y pasa al equipo de ventas.</p>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5><span class="estado-badge" style="background:#6c757d;">Descartado</span></h5>
                <p>No era buen prospecto (no tiene presupuesto, no es target, ya tiene proveedor, etc.). <strong>No es fracaso</strong> — descartar rápido a los malos prospectos te libera para enfocarte en los buenos.</p>
            </div>

            <div class="tip">
                <strong>💡 La métrica clave: tasa de calificación.</strong> Es <em>calificados ÷ total de leads × 100</em>. Si llegan 10 leads y solo 1 califica, tu tasa es 10%. Si llegan 5 y 3 califican, tu tasa es 60%. La segunda situación es mejor aunque tengas menos volumen.
            </div>
        </section>

        <!-- 4. CAPTURAR -->
        <section class="seccion" id="capturar">
            <h2>4. Capturar un lead</h2>
            <p>Cada vez que aparece un interesado (te escribe, te llama, llena un formulario, te refieren a alguien):</p>

            <div class="paso"><span class="paso-num">1</span>
                <h5>Menú <kbd>Marketing</kbd> → <kbd>Nuevo lead</kbd></h5>
                <p>O desde la lista de leads, botón "+ Nuevo lead".</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Llena lo mínimo:</h5>
                <ul>
                    <li><strong>Nombre completo*</strong> (lo único realmente obligatorio).</li>
                    <li><strong>Empresa</strong> (texto libre — todavía no la creamos formalmente en el CRM).</li>
                    <li><strong>Cargo, email, teléfono</strong> — todo lo que tengas.</li>
                    <li><strong>Fuente del lead</strong> — <strong>esto es importante</strong>: ¿de dónde salió? LinkedIn, Referido, Evento, etc.</li>
                    <li><strong>Notas</strong> — qué busca, contexto, cualquier cosa relevante.</li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Estado inicial: "Nuevo"</h5>
                <p>Si ya hablaste con él/ella, puedes ponerlo directamente como "Contactado".</p>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5>Guardar</h5>
                <p>Quedas en la ficha del lead. Desde ahí puedes cambiar estado o convertirlo.</p>
            </div>

            <div class="warning">
                <strong>⚠️ El error más común:</strong> capturar el lead pero olvidarse de la <strong>fuente</strong>.
                Sin fuente, el análisis de "¿qué canal funciona mejor?" no se puede hacer. Si no recuerdas
                la fuente, deja "Sin especificar" — pero trata de ser disciplinada con esto.
            </div>
        </section>

        <!-- 5. MOVER -->
        <section class="seccion" id="mover">
            <h2>5. Mover un lead por el embudo</h2>
            <p>Conforme avanza la conversación con el lead, vas cambiando su estado:</p>

            <div class="paso"><span class="paso-num">1</span>
                <h5>Entra a la ficha del lead</h5>
                <p><kbd>Marketing</kbd> → <kbd>Leads</kbd> → clic en el nombre.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Tarjeta "Cambiar estado" (columna derecha)</h5>
                <p>Verás botones para mover al lead a cualquier otro estado en un clic.</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Si lo descartas, te pedirá el motivo</h5>
                <p>Ejemplos: <em>"Precio"</em>, <em>"No respondió"</em>, <em>"No es target"</em>, <em>"Ya tiene proveedor"</em>. Esto sirve para identificar patrones.</p>
            </div>

            <div class="tip">
                <strong>💡 Cuando un lead pase a "Calificado":</strong> normalmente quieres convertirlo a oportunidad CRM (sección 6) para que el equipo de ventas le haga seguimiento formal. Si no lo conviertes, el lead se queda en "calificado" pero no entra al pipeline comercial.
            </div>
        </section>

        <!-- 6. CONVERTIR -->
        <section class="seccion" id="convertir">
            <h2>6. Convertir un lead a oportunidad CRM</h2>
            <p>Cuando ya validaste que el lead es real (tiene necesidad, presupuesto, autoridad), lo <strong>conviertes a oportunidad CRM</strong> con un clic. El sistema crea automáticamente <strong>empresa + contacto + oportunidad</strong> en el CRM, todo enlazado al lead.</p>

            <div class="paso"><span class="paso-num">1</span>
                <h5>Desde la ficha del lead, botón verde "Convertir a oportunidad"</h5>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>Llena 3 datos en el modal:</h5>
                <ul>
                    <li><strong>Razón social</strong> de la empresa (ya viene precargado con lo que tenías).</li>
                    <li><strong>Título</strong> de la oportunidad — ej: "Implementación SST 2026".</li>
                    <li><strong>Valor estimado</strong> en pesos (puedes poner 0 si todavía no sabes).</li>
                </ul>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>Confirmar</h5>
                <p>El sistema:</p>
                <ul>
                    <li>Crea la <strong>empresa</strong> en el CRM con los datos del lead.</li>
                    <li>Crea el <strong>contacto</strong> dentro de esa empresa.</li>
                    <li>Crea la <strong>oportunidad</strong> en etapa Prospecto (la primera del pipeline).</li>
                    <li>Marca el lead como "Calificado" y lo enlaza a la oportunidad.</li>
                    <li>Te lleva directo a la oportunidad recién creada en el CRM.</li>
                </ul>
            </div>

            <div class="warning">
                <strong>⚠️ Convertir es irreversible.</strong> Si te equivocaste, tendrás que ir al CRM
                y eliminar la oportunidad y la empresa a mano. Trata de convertir solo cuando estés segura.
            </div>

            <div class="tip">
                <strong>💡 ¿Quién hace seguimiento después?</strong> El responsable del lead se mantiene en la
                empresa y la oportunidad. Después, el vendedor toma la oportunidad en el Kanban del CRM
                y la mueve por las etapas (Calificado → Propuesta → Negociación → Ganada/Perdida).
            </div>
        </section>

        <!-- 7. DIARIO -->
        <section class="seccion" id="diario">
            <h2>7. El diario de acciones</h2>
            <p>El diario es <strong>el log de cada cosa que haces de marketing</strong>: publicaste un post, fuiste a un evento, mandaste correos en frío, hiciste una llamada, etc.</p>

            <div class="warning">
                <strong>⚠️ Sin el diario, los números del dashboard no se pueden interpretar.</strong>
                Si esta semana los leads bajaron 30% pero no anotaste nada, no podemos saber por qué.
                Si bajaron y anotaste que no publicaste nada porque estuviste de viaje, ya sabes.
            </div>

            <h3 id="diario-que">7.1 ¿Qué anotar?</h3>
            <p><strong>Regla simple: si lo hiciste para generar leads o cuidar leads, anótalo.</strong></p>
            <ul>
                <li>Publiqué post en LinkedIn sobre SST.</li>
                <li>Llamé en frío a 10 empresas constructoras.</li>
                <li>Asistí a la feria de PH del Centro 92.</li>
                <li>Mandé propuesta a 3 leads de la semana pasada.</li>
                <li>Tuve reunión de seguimiento con el cliente X (sí, esto también cuenta).</li>
            </ul>
            <p>Y para cada una, registra:</p>
            <ul>
                <li><strong>Fecha</strong> en que la hiciste.</li>
                <li><strong>Tipo</strong> (Post LinkedIn, Correo en frío, Evento, etc. — el admin puede configurar más).</li>
                <li><strong>Descripción</strong> corta (qué hiciste exactamente).</li>
                <li><strong>Costo</strong> (opcional, solo si gastaste plata — ver 7.2).</li>
                <li><strong>Leads generados</strong> (opcional, si sabes cuántos leads vinieron de ahí).</li>
            </ul>

            <h3 id="diario-costo">7.2 ¿Cuándo poner costo?</h3>
            <p>Solo cuando la acción tuvo <strong>costo de bolsillo</strong>:</p>
            <ul>
                <li>Anuncio pagado en LinkedIn / Google → sí, pon el monto exacto.</li>
                <li>Asistencia a evento (boleta, viáticos, regalos) → sí.</li>
                <li>Contratación de un freelance para diseño → sí.</li>
                <li>Publicación en LinkedIn (tu tiempo) → <strong>no</strong>, deja vacío. El tiempo personal no se cuenta aquí.</li>
                <li>Llamada en frío → <strong>no</strong>.</li>
            </ul>

            <div class="tip">
                <strong>💡 Para qué sirve el costo:</strong> el dashboard calcula el <strong>CAC</strong>
                (Costo de Adquisición de Cliente) del mes = costo total ÷ leads del mes. Si gastaste
                $200.000 en eventos y trajiste 20 leads, tu CAC informal es $10.000/lead. Eso te dice
                si lo que invertiste valió la pena.
            </div>
        </section>

        <!-- 8. DASHBOARD -->
        <section class="seccion" id="dashboard">
            <h2>8. Cómo leer el dashboard</h2>
            <p>Menú <kbd>Marketing</kbd> → <kbd>Dashboard</kbd>. Tiene 4 cuadros de KPIs arriba y 4 gráficos/tablas abajo.</p>

            <h3>Los 4 KPIs</h3>
            <ul>
                <li><strong>Leads esta semana</strong> — cuántos leads nuevos capturaste de lunes a hoy. Junto al número aparece la comparación con la semana pasada (▲ verde si subió, ▼ rojo si bajó).</li>
                <li><strong>Tasa de calificación</strong> — qué porcentaje de tus leads están calificados (vs total histórico). Idealmente sube con el tiempo: significa que cada vez atraes leads más relevantes.</li>
                <li><strong>Acciones este mes</strong> — cuántas registraste en el diario. Junto sale el costo total si pusiste alguno.</li>
                <li><strong>CAC del mes</strong> — costo ÷ leads. Solo aparece si registraste costos.</li>
            </ul>

            <h3>Los 4 gráficos</h3>
            <ul>
                <li><strong>Leads por semana</strong> (barras) — las últimas 8 semanas. Sirve para ver tendencia.</li>
                <li><strong>Leads por estado</strong> (donut) — cómo se distribuyen los leads en el embudo.</li>
                <li><strong>Top fuentes por calificación</strong> (tabla) — <strong>la tabla clave del módulo</strong>. Fíjate en la columna "Calificados", no en "Total". La fuente que CALIFICA mejor vale más que la que trae más volumen.</li>
                <li><strong>Acciones del mes por tipo</strong> (barras) — qué tipos de acción dominaron este mes.</li>
            </ul>

            <div class="ejemplo">
                <strong>✅ Ejemplo de interpretación:</strong> "Esta semana tuve 8 leads (vs 5 la semana pasada,
                ▲ 60%). Pero solo 1 calificó. Mirando la tabla de fuentes, los <strong>referidos</strong>
                me dan 5 calificados de 8 leads (62%), mientras que LinkedIn me da 1 de 15 (6%).
                <strong>Conclusión:</strong> debo pedir más referidos y bajarle ritmo a LinkedIn."
            </div>
        </section>

        <!-- 9. OTTO -->
        <section class="seccion" id="otto">
            <h2>9. OTTO Coach de Marketing</h2>
            <p>OTTO es un <strong>asistente IA</strong> (basado en Claude Sonnet) entrenado para ayudarte a interpretar los datos del módulo. Es como tener un <strong>experto en marketing de bolsillo</strong> que mira tus números y te dice qué hacer.</p>
            <p>Compromiso de OTTO: <strong>nunca inventa cifras</strong>. Si no tiene datos suficientes para responder algo, lo dice claramente.</p>

            <h3 id="otto-uso">9.1 Cómo usarlo</h3>
            <p>Hay dos formas:</p>
            <div class="paso"><span class="paso-num">A</span>
                <h5>Pantalla dedicada</h5>
                <p>Menú <kbd>Marketing</kbd> → <kbd>OTTO Coach de Marketing</kbd>. Verás 5 tarjetas de análisis predefinido. Da clic en la que necesites.</p>
            </div>
            <div class="paso"><span class="paso-num">B</span>
                <h5>Widget flotante</h5>
                <p>Botón circular oscuro en la esquina inferior derecha (visible en todas las pantallas de Marketing). Lo abres con un clic y puedes preguntarle lo que quieras en lenguaje natural.</p>
                <p>Ejemplos de preguntas libres:</p>
                <ul>
                    <li><em>"¿Cómo voy con los referidos?"</em></li>
                    <li><em>"¿Cuánto cuesta cada lead este mes?"</em></li>
                    <li><em>"¿Qué hago si solo tengo 30 minutos esta tarde?"</em></li>
                </ul>
            </div>

            <h3 id="otto-presets">9.2 Los 5 análisis predefinidos</h3>
            <div class="paso"><span class="paso-num">1</span>
                <h5>📈 ¿Avanzamos esta semana?</h5>
                <p>OTTO compara los datos de esta semana vs la pasada y te dice si avanzaste, te estancaste o retrocediste. Es el análisis estrella para los lunes en la mañana.</p>
            </div>
            <div class="paso"><span class="paso-num">2</span>
                <h5>🎯 ¿Qué fuente está funcionando?</h5>
                <p>Identifica las 3 fuentes clave: la <strong>estrella</strong> (mucho califica), la <strong>trampa</strong> (mucho volumen pero no califica), la <strong>oportunidad</strong> (poco volumen pero gran tasa, vale duplicar esfuerzo).</p>
            </div>
            <div class="paso"><span class="paso-num">3</span>
                <h5>🥶 Leads que se están enfriando</h5>
                <p>Lista de leads sin actualizar en 7+ días, con un plan de rescate. Útil para los lunes: "¿a quién tengo que reactivar esta semana?".</p>
            </div>
            <div class="paso"><span class="paso-num">4</span>
                <h5>🔍 ¿Estoy haciendo lo suficiente?</h5>
                <p>Diagnóstico de tu diario de acciones del mes: cantidad, distribución por tipo, tendencia semanal. Veredicto: suficiente / insuficiente / poco diverso.</p>
            </div>
            <div class="paso"><span class="paso-num">5</span>
                <h5>🚀 Plan de marketing de esta semana</h5>
                <p>El más completo: OTTO mira todos tus datos (embudo, fuentes, leads estancados, acciones recientes) y te da un <strong>plan accionable de 3-5 cosas concretas que hacer esta semana</strong>, con el porqué basado en datos.</p>
            </div>

            <div class="warning">
                <strong>⚠️ Límite mensual:</strong> el consumo de OTTO está topado en <strong>$5 USD al mes</strong>
                (compartido con OTTO financiero y OTTO comercial). Cada consulta cuesta fracciones de centavo,
                así que normalmente sobra — pero si llegas al límite, OTTO se desactiva hasta el siguiente mes.
            </div>
        </section>

        <!-- 10. DISCIPLINA -->
        <section class="seccion" id="disciplina">
            <h2>10. Disciplina semanal recomendada</h2>
            <p>El módulo solo funciona si lo alimentas regularmente. <strong>Te recomiendo una rutina simple:</strong></p>

            <div class="ejemplo">
                <strong>Lunes 9:00 am — 15 minutos:</strong>
                <ol class="mb-0 mt-1">
                    <li>Lanzar el análisis "<strong>📈 ¿Avanzamos esta semana?</strong>" en OTTO Coach.</li>
                    <li>Leer el veredicto y las acciones sugeridas.</li>
                    <li>Lanzar "<strong>🥶 Leads que se están enfriando</strong>" para ver a quién rescatar primero.</li>
                </ol>
            </div>

            <div class="ejemplo">
                <strong>Cada vez que hagas algo de marketing — 30 segundos:</strong>
                <p class="mb-0 mt-1">Registrar la acción en el diario. Hazlo en el momento, no al final de la semana — es más fácil acordarse.</p>
            </div>

            <div class="ejemplo">
                <strong>Cada vez que llegue un lead nuevo — 1 minuto:</strong>
                <p class="mb-0 mt-1">Capturarlo en el módulo con su fuente correcta. Si pasas más de un día sin hacerlo, lo más probable es que olvides datos importantes.</p>
            </div>

            <div class="ejemplo">
                <strong>Viernes 4:00 pm — 15 minutos:</strong>
                <ol class="mb-0 mt-1">
                    <li>Revisar la lista de leads en estado "Nuevo": ¿alguno lleva más de 3 días sin contactar?</li>
                    <li>Lanzar "<strong>🚀 Plan de marketing de esta semana</strong>" en OTTO para preparar el lunes.</li>
                </ol>
            </div>
        </section>

        <!-- 11. FAQ -->
        <section class="seccion" id="faq">
            <h2>11. Preguntas frecuentes</h2>

            <h3>¿Tengo que registrar TODOS los leads o solo los "buenos"?</h3>
            <p><strong>Todos.</strong> Incluso los que pintan mal. El valor del módulo está en aprender qué fuentes
            traen buenos vs malos prospectos — eso solo se ve si registras también los que terminas descartando.</p>

            <h3>Si una persona me contacta por LinkedIn pero después negociamos por correo, ¿qué fuente pongo?</h3>
            <p>La fuente del <strong>primer contacto</strong>: LinkedIn. Donde nos descubrió, no el medio que usamos después.</p>

            <h3>¿Qué pasa si por error convierto un lead a oportunidad?</h3>
            <p>El sistema crea la empresa + contacto + oportunidad en el CRM. Para revertir, debes ir al CRM
            y eliminar la oportunidad manualmente (la empresa y el contacto puedes dejarlos si los vas a usar
            después). El lead queda como "calificado" en marketing.</p>

            <h3>¿OTTO ve solo mis leads o los de todo el equipo?</h3>
            <p>OTTO ve el embudo completo del módulo. No filtra por usuario — analiza el conjunto. Si quieres
            ver solo tus leads, usa los filtros de la lista de leads (responsable = tú).</p>

            <h3>¿Puedo cambiar los tipos de acción del diario?</h3>
            <p>Sí, pero solo los admin (Edison + admins de CRM). En <kbd>Marketing</kbd> → <kbd>Configuración</kbd>
            → <kbd>Tipos de acción</kbd> puedes agregar, editar o desactivar tipos con su propio color.</p>

            <h3>El dashboard dice CAC "—". ¿Por qué?</h3>
            <p>No hay datos para calcularlo. Necesitas <strong>al menos una acción con costo registrado</strong>
            Y <strong>al menos un lead nuevo este mes</strong>. Si llevas un mes sin gastar nada de marketing,
            el CAC quedará vacío — es esperado.</p>

            <h3>OTTO me dice "no hay datos suficientes". ¿Qué hago?</h3>
            <p>Empieza a alimentar el módulo: captura todos los leads que lleguen y registra todas las acciones
            que hagas. En 2-3 semanas OTTO tendrá suficiente para empezar a darte análisis útiles.</p>

            <h3>¿Puedo usar este módulo si solo soy yo en marketing?</h3>
            <p>Sí, está hecho para eso. La filosofía es justamente que una persona sola pueda saber qué medir
            y qué acciones controlar — sin necesitar un equipo de marketing entrenado.</p>
        </section>

        <div class="text-center text-muted small py-3">
            ¿Algo no quedó claro o falta un caso? Avísale a Edison para actualizar el manual.
        </div>

        </div><!-- /col-9 -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Marcar la sección activa en el TOC mientras se hace scroll
(function() {
    const links = document.querySelectorAll('.toc a');
    const sections = document.querySelectorAll('.seccion');
    function actualizarActivo() {
        let idActiva = null;
        sections.forEach(s => {
            const top = s.getBoundingClientRect().top;
            if (top < 120) idActiva = s.id;
        });
        links.forEach(a => {
            a.classList.toggle('active', a.getAttribute('href') === '#' + idActiva);
        });
    }
    window.addEventListener('scroll', actualizarActivo);
    actualizarActivo();
})();
</script>
</body>
</html>
